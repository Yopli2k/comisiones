<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Comisiones\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Join\LiquidacionComisionFactura;

/**
 * Description of EditCommissionSettlement
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class EditLiquidacionComision extends EditController
{
    const INSERT_DOMICILED_ALL = 'ALL';
    const INSERT_DOMICILED_DOMICILED = 'DOMICILED';
    const INSERT_DOMICILED_WITHOUT = 'WITHOUT';
    const INSERT_STATUS_ALL = 'ALL';
    const INSERT_STATUS_CHARGED = 'CHARGED';
    const VIEWNAME_SETTLEDINVOICE = 'ListLiquidacionComisionFactura';

    public function getModelClassName(): string
    {
        return 'LiquidacionComision';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'settlement';
        $data['icon'] = 'fa-solid fa-chalkboard-teacher';
        return $data;
    }

    /**
     * Calculate the commission percentage for each of the selected invoices
     */
    protected function calculateCommission(): bool
    {
        $data = $this->request->request->all();
        $docs = $this->getInvoicesFromDataForm($data);
        if (empty($docs)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        $this->dataBase->beginTransaction();

        try {
            // recalculate all business documents and save new totals
            foreach ($docs as $invoice) {
                $lines = $invoice->getLines();
                if (false === Calculator::calculate($invoice, $lines, true)) {
                    throw new Exception(
                        Tools::lang()->trans('error-calculate-commission', ['%code%' => $invoice->codigo])
                    );
                }
            }

            // update total to settlement commission
            $this->calculateTotalCommission();

            // confirm changes
            $this->dataBase->commit();

            Tools::log()->notice('record-updated-correctly');
        } catch (Exception $exc) {
            $this->dataBase->rollback();
            Tools::log()->error($exc->getMessage());
        }

        return true;
    }

    /**
     * Calculate the total commission amount for the settlement
     */
    protected function calculateTotalCommission(): bool
    {
        $code = $this->request->query->get('code');
        $this->getModel()->calculateTotalCommission($code);
        return true;
    }

    /**
     * Add view with Invoices included
     *
     * @param string $viewName
     */
    protected function createSettledInvoiceView(string $viewName = self::VIEWNAME_SETTLEDINVOICE): void
    {
        $this->addListView($viewName, 'Join\LiquidacionComisionFactura', 'invoices', 'fa-solid fa-file-invoice')
            ->setSettings('modalInsert', 'insertinvoices')
            ->addOrderBy(['fecha', 'idfactura'], 'date', 2)
            ->addOrderBy(['total'], 'amount')
            ->addOrderBy(['totalcomision'], 'commission');
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createSettledInvoiceView();
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action == 'generateinvoice') {
            $this->generateInvoice();
            return;
        }

        parent::execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'calculatecommission':
                return $this->calculateCommission();

            case 'delete':
                parent::execPreviousAction($action);
                return $this->calculateTotalCommission();

            case 'insertinvoices':
                return $this->insertInvoices();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Create the invoice for the payment to the agent
     */
    protected function generateInvoice(): bool
    {
        if ($this->views[$this->getMainViewName()]->model->generateInvoice()) {
            Tools::log()->notice('record-updated-correctly');

            // redireccionamos a la factura
            $invoice = new FacturaProveedor();
            if ($invoice->load($this->views[$this->getMainViewName()]->model->idfactura)) {
                $this->redirect($invoice->url() . '&action=save-ok');
            }

            return true;
        }

        Tools::log()->error('record-save-error');
        return false;
    }

    /**
     * Get the list of invoices selected by the user
     *
     * @param array $data
     *
     * @return FacturaCliente[]
     */
    protected function getInvoicesFromDataForm(array $data): array
    {
        if (!isset($data['code'])) {
            return [];
        }

        $selected = implode(',', $data['code']);
        if (empty($selected)) {
            return [];
        }

        $where = [Where::column('idfactura', $selected, 'IN')];
        return FacturaCliente::all($where, ['idfactura' => 'ASC']);
    }

    /**
     * Gets a where filter of the data reported in the form
     *
     * @param array $data
     *
     * @return array[]
     */
    protected function getInvoicesWhere(array $data): array
    {
        // Basic data filter
        $where = [
            Where::column('facturascli.idempresa', $data['idempresa']),
            Where::column('facturascli.codserie', $data['codserie']),
            Where::column('facturascli.codagente', $data['codagente'])
        ];

        // Date filter
        if (!empty($data['datefrom'])) {
            $where[] = Where::column('facturascli.fecha', $data['datefrom'], '>=');
        }
        if (!empty($data['dateto'])) {
            $where[] = Where::column('facturascli.fecha', $data['dateto'], '<=');
        }

        // Status payment filter
        if ($data['status'] == self::INSERT_STATUS_CHARGED) {
            $where[] = Where::column('facturascli.pagada', true);
        }

        // Payment source filter
        switch ($data['domiciled']) {
            case self::INSERT_DOMICILED_DOMICILED:
                $where[] = Where::column('formaspago.domiciliado', true);
                break;

            case self::INSERT_DOMICILED_WITHOUT:
                $where[] = Where::column('formaspago.domiciliado', false);
                break;
        }

        // Customer filter
        if (!empty($data['codcliente'])) {
            $where[] = Where::column('facturascli.codcliente', $data['codcliente']);
        }

        // Return completed filter
        return $where;
    }

    /**
     * Insert Invoices in the settled
     */
    protected function insertInvoices(): bool
    {
        $data = $this->request->request->all();

        // add new invoice to settlement commission
        $where = $this->getInvoicesWhere($data);
        $settleinvoice = new LiquidacionComisionFactura();
        $settleinvoice->addInvoiceToSettle($data['idliquidacion'], $where);

        // update total to settlement commission
        return $this->calculateTotalCommission();
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     * @throws Exception
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEWNAME_SETTLEDINVOICE:
                $this->loadDataSettledInvoice($view);
                $this->setViewStatus($viewName, $view);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load data to view with Invoices detaill
     *
     * @param BaseView $view
     */
    protected function loadDataSettledInvoice($view): void
    {
        // Get master data
        $idsettled = $this->getModel()->idliquidacion;
        if (empty($idsettled)) {
            return;
        }

        // Set master values to insert modal view
        $view->model->codagente = $this->getModel()->codagente;
        $view->model->codserie = $this->getModel()->codserie;
        $view->model->idempresa = $this->getModel()->idempresa;
        $view->model->idliquidacion = $idsettled;

        // Load view data
        $view->loadData('', [
            new DataBaseWhere('facturascli.idliquidacion', $idsettled),
        ]);
    }

    /**
     * Allows you to set special conditions for columns and action buttons
     * based on the state of the views
     *
     * @param string $viewName
     * @param BaseView $view
     * @throws Exception
     */
    protected function setViewStatus(string $viewName, BaseView $view): void
    {
        if ($view->count === 0) {
            $this->setSettings($viewName, 'btnDelete', false);
            return;
        }

        $canInvoice = empty($this->getModel()->idfactura);
        $mainViewName = $this->getMainViewName();
        $this->views[$mainViewName]->disableColumn('company', false, 'true')
            ->setSettings('btnNew', $canInvoice)
            ->setSettings('btnDelete', $canInvoice)
            ->disableColumn('serie', false, 'true')
            ->disableColumn('agent', false, 'true');

        if ($canInvoice) {
            $this->addButton($viewName, [
                'action' => 'calculatecommission',
                'confirm' => 'true',
                'icon' => 'fa-solid fa-percentage',
                'label' => 'calculate'
            ]);

            $this->addButton($mainViewName, [
                'action' => 'generateinvoice',
                'color' => 'info',
                'confirm' => true,
                'icon' => 'fa-solid fa-file-invoice',
                'label' => 'generate-invoice'
            ]);
        }
    }
}

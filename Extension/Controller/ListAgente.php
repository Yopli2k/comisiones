<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Comisiones\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Dinamic\Lib\CommissionTools;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Description of ListAgente
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListAgente
{
    public function createViews() {
        return function() {
            $this->createSettlementView();
            $this->createCommissionView();
            $this->createPenaltyView();
        };
    }

    /**
     * Add Commission View
     *
     * @param string $viewName
     */
    protected function createCommissionView()
    {
        return function (string $viewName = 'ListComision') {
            $this->addView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
            $this->addOrderBy($viewName, ['idcomision'], 'code');
            $this->addOrderBy($viewName, ['prioridad'], 'priority', 2);
            $this->addOrderBy($viewName, ['idempresa', 'codagente', 'porcentaje'], 'company');
            $this->addOrderBy($viewName, ['codagente', 'codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'agent');
            $this->addOrderBy($viewName, ['codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'customer');
            $this->addOrderBy($viewName, ['codfamilia', 'idproducto', 'porcentaje'], 'family');
            $this->addSearchFields($viewName, ['codagente', 'codcliente']);

            // Filters
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
            $this->addFilterAutocomplete($viewName, 'agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre');
            $this->addFilterAutocomplete($viewName, 'customer', 'customer', 'codcliente', 'Cliente', 'codcliente');
            $this->addFilterAutocomplete($viewName, 'family', 'family', 'codfamilia', 'Familia', 'codfamilia');
            $this->addFilterAutocomplete($viewName, 'product', 'product', 'referencia', 'Producto', 'referencia', 'descripcion');
        };
    }

    /**
     * Add Penalty Commission View
     *
     * @param string $viewName
     */
    protected function createPenaltyView()
    {
        return function (string $viewName = 'ListComisionPenalizacion') {
            $this->addView($viewName, 'ComisionPenalizacion', 'penalize', 'fas fa-minus-circle');
            $this->addOrderBy($viewName, ['id'], 'code');
            $this->addOrderBy($viewName, ['idempresa', 'codagente', 'dto_desde'], 'company');
            $this->addOrderBy($viewName, ['codagente', 'idempresa', 'dto_desde'], 'agent', 1);

            // Filters
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
            $this->addFilterAutocomplete($viewName, 'agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre');
        };
    }

    /**
     * Add Settled Commission View
     *
     * @param string $viewName
     */
    protected function createSettlementView()
    {
        return function (string $viewName = 'ListLiquidacionComision') {
            $this->addView($viewName, 'LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
            $this->addOrderBy($viewName, ['fecha', 'idliquidacion'], 'date', 2);
            $this->addOrderBy($viewName, ['codagente', 'fecha'], 'agent');
            $this->addOrderBy($viewName, ['total', 'fecha'], 'amount');
            $this->addSearchFields($viewName, ['observaciones']);

            // Filters
            $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
            $this->addFilterSelect($viewName, 'codserie', 'serie', 'codserie', Series::codeModel());
            $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', Agentes::codeModel());

            $this->addButton($viewName, [
                'action' => 'gen-settlements',
                'icon' => 'fas fa-magic',
                'label' => 'generate',
                'type' => 'modal'
            ]);
        };
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction()
    {
        return function($action) {
            if ($action === 'gen-settlements') {
                $this->generateSettlementsAction();
            }

            return true;
        };
    }

    protected function generateSettlementsAction()
    {
        return function () {
            $codserie = $this->request->request->get('codserie', '');
            $dateFrom = $this->request->request->get('datefrom', '');
            $dateTo = $this->request->request->get('dateto', '');
            $idempresa = $this->request->request->get('idempresa');

            $generated = 0;
            foreach (Agentes::all() as $agente) {
                $invoiceModel = new FacturaCliente();
                $where = [
                    new DataBaseWhere('idliquidacion', null, 'IS'),
                    new DataBaseWhere('idempresa', $idempresa),
                    new DataBaseWhere('codserie', $codserie),
                    new DataBaseWhere('codagente', $agente->codagente)
                ];

                if (!empty($dateFrom)) {
                    $where[] = new DataBaseWhere('fecha', $dateFrom, '>=');
                }

                if (!empty($dateTo)) {
                    $where[] = new DataBaseWhere('fecha', $dateTo, '<=');
                }

                $invoices = $invoiceModel->all($where, [], 0, 0);
                if (count($invoices)) {
                    $this->newSettlement($agente->codagente, $idempresa, $codserie, $invoices);
                    $generated++;
                }
            }

            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $generated]);
        };
    }

    /**
     * @param string $codagente
     * @param int $idempresa
     * @param string $codserie
     * @param FacturaCliente[] $invoices
     */
    protected function newSettlement()
    {
        return function ($codagente, $idempresa, $codserie, $invoices) {
            $newSettlement = new LiquidacionComision();
            $newSettlement->codagente = $codagente;
            $newSettlement->codserie = $codserie;
            $newSettlement->idempresa = $idempresa;
            if ($newSettlement->save()) {
                $commissionTools = new CommissionTools();
                foreach ($invoices as $invoice) {
                    // recalculate commissions
                    $lines = $invoice->getLines();
                    $commissionTools->recalculate($invoice, $lines);

                    $invoice->idliquidacion = $newSettlement->idliquidacion;
                    $invoice->save();
                }

                $newSettlement->calculateTotalCommission($newSettlement->idliquidacion);
            }
        };
    }
}
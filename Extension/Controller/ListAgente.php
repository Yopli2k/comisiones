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

namespace FacturaScripts\Plugins\Comisiones\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Description of ListAgente
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez      <hola@danielfg.es>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListAgente
{
    public function createViews(): Closure
    {
        return function () {
            $this->createSettlementView();
            $this->createCommissionView();
            $this->createPenaltyView();
        };
    }

    protected function createCommissionView(): Closure
    {
        return function (string $viewName = 'ListComision') {
            $this->addView($viewName, 'Comision', 'commissions', 'fa-solid fa-percentage')
                ->addOrderBy(['idcomision'], 'code')
                ->addOrderBy(['prioridad'], 'priority', 2)
                ->addOrderBy(['idempresa', 'codagente', 'porcentaje'], 'company')
                ->addOrderBy(['codagente', 'codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'agent')
                ->addOrderBy(['codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'customer')
                ->addOrderBy(['codfamilia', 'idproducto', 'porcentaje'], 'family')
                ->addSearchFields(['codagente', 'codcliente'])
                ->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel())
                ->addFilterAutocomplete('agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre')
                ->addFilterAutocomplete('customer', 'customer', 'codcliente', 'Cliente', 'codcliente')
                ->addFilterAutocomplete('family', 'family', 'codfamilia', 'Familia', 'codfamilia')
                ->addFilterAutocomplete('product', 'product', 'referencia', 'Producto', 'referencia', 'descripcion');
        };
    }

    protected function createPenaltyView(): Closure
    {
        return function (string $viewName = 'ListComisionPenalizacion') {
            $this->addView($viewName, 'ComisionPenalizacion', 'penalize', 'fa-solid fa-minus-circle')
                ->addOrderBy(['id'], 'code')
                ->addOrderBy(['idempresa', 'codagente', 'dto_desde'], 'company')
                ->addOrderBy(['codagente', 'idempresa', 'dto_desde'], 'agent', 1)
                ->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel())
                ->addFilterAutocomplete('agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre');
        };
    }

    protected function createSettlementView(): Closure
    {
        return function (string $viewName = 'ListLiquidacionComision') {
            $this->addView($viewName, 'LiquidacionComision', 'settlements', 'fa-solid fa-chalkboard-teacher')
                ->addOrderBy(['fecha', 'idliquidacion'], 'date', 2)
                ->addOrderBy(['codagente', 'fecha'], 'agent')
                ->addOrderBy(['total', 'fecha'], 'amount')
                ->addSearchFields(['observaciones'])
                ->addFilterPeriod('fecha', 'date', 'fecha')
                ->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel())
                ->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel())
                ->addFilterSelect('codagente', 'agent', 'codagente', Agentes::codeModel());

            $this->addButton($viewName, [
                'action' => 'gen-settlements',
                'icon' => 'fa-solid fa-magic',
                'label' => 'generate',
                'type' => 'modal'
            ]);
        };
    }

    protected function execPreviousAction(): Closure
    {
        return function ($action) {
            if ($action === 'gen-settlements') {
                $this->generateSettlementsAction();
            }
        };
    }

    protected function generateSettlementsAction(): Closure
    {
        return function () {
            $codserie = $this->request->request->get('codserie', '');
            $dateFrom = $this->request->request->get('datefrom', '');
            $dateTo = $this->request->request->get('dateto', '');
            $idempresa = $this->request->request->get('idempresa');

            $generated = 0;
            foreach (Agentes::all() as $agente) {
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

                $invoices = FacturaCliente::all($where, [], 0, 0);
                if (count($invoices)) {
                    $this->newSettlement($agente->codagente, $idempresa, $codserie, $invoices);
                    $generated++;
                }
            }

            Tools::log()->notice('items-added-correctly', ['%num%' => $generated]);
        };
    }

    protected function newSettlement(): Closure
    {
        return function ($codagente, $idempresa, $codserie, $invoices) {
            $newSettlement = new LiquidacionComision();
            $newSettlement->codagente = $codagente;
            $newSettlement->codserie = $codserie;
            $newSettlement->idempresa = $idempresa;
            if ($newSettlement->save()) {
                foreach ($invoices as $invoice) {
                    // recalculate commissions
                    $lines = $invoice->getLines();
                    Calculator::calculate($invoice, $lines, false);

                    $invoice->idliquidacion = $newSettlement->idliquidacion;
                    $invoice->save();
                }

                $newSettlement->calculateTotalCommission($newSettlement->idliquidacion);
            }
        };
    }
}

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

/**
 * Description of EditAgente
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditAgente
{
    public function createViews() {
        return function() {
            $this->createCommissionsView();
            $this->createSettlementView();
        };
    }

    /**
     *
     * @param string $viewName
     */
    protected function createCommissionsView()
    {
        return function (string $viewName = 'ListComision') {
            $this->addListView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
            $this->views[$viewName]->addOrderBy(['prioridad'], 'priority', 2);
            $this->views[$viewName]->addOrderBy(['porcentaje'], 'percentage');

            /// disable columns
            $this->views[$viewName]->disableColumn('agent', true);
        };
    }

    /**
     *
     * @param string $viewName
     */
    protected function createSettlementView()
    {
        return function (string $viewName = 'ListLiquidacionComision') {
            $this->addListView($viewName, 'LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
            $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
            $this->views[$viewName]->addOrderBy(['total'], 'amount');
        };
    }

    protected function loadData()
    {
        return function ($viewName, $view) {
            switch ($viewName) {
                case 'ListComision':
                case 'ListLiquidacionComision':
                $codagente = $this->getViewModelValue('EditAgente', 'codagente');
                $where = [new DataBaseWhere('codagente', $codagente)];
                $view->loadData('', $where);
                break;
            }
        };
    }
}
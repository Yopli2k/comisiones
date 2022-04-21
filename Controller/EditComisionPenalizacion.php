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

namespace FacturaScripts\Plugins\Comisiones\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controler to edit commission.
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditComisionPenalizacion extends EditController
{

    public function getModelClassName(): string
    {
        return 'ComisionPenalizacion';
    }

    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'admin';
        $pagedata['title'] = 'penalize';
        $pagedata['icon'] = 'fas fa-minus-circle';
        return $pagedata;
    }

    protected function createViews()
    {
        parent::createViews();

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }
    }
}

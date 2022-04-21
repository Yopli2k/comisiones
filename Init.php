<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
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

namespace FacturaScripts\Plugins\Comisiones;

use FacturaScripts\Core\Base\AjaxForms\SalesFooterHTML;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Description of Init
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Init extends InitClass
{
    public function init()
    {
        $this->loadExtension(new Extension\Controller\ListAgente());
        $this->loadExtension(new Extension\Controller\EditAgente());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Model\Base\SalesDocumentLine());
        Calculator::addMod(new Mod\CalculatorMod());
        SalesFooterHTML::addMod(new Mod\SalesFooterHTMLMod());
    }

    public function update()
    {
        new LiquidacionComision();
    }
}
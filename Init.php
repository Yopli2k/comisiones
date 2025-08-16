<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
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

namespace FacturaScripts\Plugins\Comisiones;

use FacturaScripts\Core\Lib\AjaxForms\SalesFooterHTML;
use FacturaScripts\Core\Lib\AjaxForms\SalesLineHTML;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;

use FacturaScripts\Plugins\Comisiones\Model\LiquidacionComision;

/**
 * Description of Init
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\ListAgente());
        $this->loadExtension(new Extension\Controller\EditAgente());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
        $this->loadExtension(new Extension\Model\Base\SalesDocumentLine());

        Calculator::addMod(new Mod\CalculatorMod());
        SalesFooterHTML::addMod(new Mod\SalesFooterHTMLMod());
        SalesLineHTML::addMod(new Mod\SalesLineHTMLMod());
    }

    public function uninstall(): void
    {
    }

    public function update(): void
    {
        new LiquidacionComision();
    }
}

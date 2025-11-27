<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\Comisiones\Mod\CalculatorMod;
use FacturaScripts\Plugins\Comisiones\Mod\SalesFooterHTMLMod;
use FacturaScripts\Plugins\Comisiones\Mod\SalesLineHTMLMod;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class ModTest extends TestCase
{
    use LogErrorsTrait;

    public function testCalculatorMod(): void
    {
        $mod = new CalculatorMod();
    }

    public function testSalesFooterHTMLMod(): void
    {
        $mod = new SalesFooterHTMLMod();
        $this->assertNotEmpty($mod->newModalFields());
    }

    public function testSalesLineHTMLMod(): void
    {
        $mod = new SalesLineHTMLMod();
        $this->assertNotEmpty($mod->newModalFields());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}

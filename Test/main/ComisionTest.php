<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Plugins\Comisiones\Model\Comision;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ComisionTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCreateToAgent()
    {
        // creamos un agente
        $agent = new Agente();
        $agent->nombre = 'Test';
        $this->assertTrue($agent->save());

        // creamos una comisión para el agente
        $com1 = new Comision();
        $com1->codagente = $agent->codagente;
        $com1->porcentaje = 10;
        $this->assertTrue($com1->save());

        // eliminamos el agente
        $this->assertTrue($agent->delete());

        // comprobamos que la comisión se ha eliminado
        $this->assertFalse($com1->exists());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}

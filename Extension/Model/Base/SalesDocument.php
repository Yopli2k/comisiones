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

namespace FacturaScripts\Plugins\Comisiones\Extension\Model\Base;

use Closure;
use FacturaScripts\Core\Tools;

class SalesDocument
{
    /**
     * % commission of the agent.
     *
     * @var float|int
     */
    public $totalcomision;

    public function clear(): Closure
    {
        return function () {
            $this->totalcomision = 0.0;
        };
    }

    public function onChange(): Closure
    {
        return function ($field) {
            if ('codagente' === $field && property_exists($this, 'idliquidacion') && $this->idliquidacion) {
                Tools::log()->warning('cant-change-agent-in-settlement');
                return false;
            }
        };
    }

    public function test(): Closure
    {
        return function () {
            if (null === $this->codagente) {
                $this->totalcomision = 0.0;
            }
        };
    }
}

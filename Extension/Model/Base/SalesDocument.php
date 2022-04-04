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

namespace FacturaScripts\Plugins\Comisiones\Extension\Model\Base;

use FacturaScripts\Core\Base\Calculator;

class SalesDocument
{
    /**
     * % commission of the agent.
     *
     * @var float|int
     */
    public $totalcomision;

    public function clear() {
        return function() {
            $this->totalcomision = 0.0;
        };
    }

    public function onChange() {
        return function($field) {
            if ('codagente' === $field) {
                return $this->onChangeAgent();
            }
        };
    }

    /**
     * @return bool
     */
    protected function onChangeAgent()
    {
        return function () {
            if ($this->idliquidacion) {
                $this->toolBox()->i18nLog()->warning('cant-change-agent-in-settlement');
                return false;
            }

            if (null !== $this->codagente && $this->total > 0) {
                $lines = $this->getLines();
                return Calculator::calculate($this, $lines, false);
            }

            return true;
        };
    }

    public function test() {
        return function() {
            if (null === $this->codagente) {
                $this->totalcomision = 0.0;
            }
            return true;
        };
    }
}
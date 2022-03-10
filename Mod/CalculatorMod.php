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

namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Base\Contract\CalculatorModInterface;
use FacturaScripts\Dinamic\Lib\CommissionTools;

/**
 * Description of CalculatorMod
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CalculatorMod implements CalculatorModInterface
{

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        // TODO: Implement apply() method.
        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        // TODO: Implement calculate() method.
        $commissionTools = new CommissionTools();
        $commissionTools->recalculate($doc, $lines);
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        // TODO: Implement calculateLine() method.
        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        // TODO: Implement clear() method.
        return true;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        // TODO: Implement getSubtotals() method.
        return true;
    }
}
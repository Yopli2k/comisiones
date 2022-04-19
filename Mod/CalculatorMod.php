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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Base\Contract\CalculatorModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\Comision;
use FacturaScripts\Dinamic\Model\ComisionPenalizacion;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Description of CalculatorMod
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CalculatorMod implements CalculatorModInterface
{

    /**
     * Commission ratio.
     *
     * @var Comision[]
     */
    protected $commissions;

    /**
     * Sales document.
     *
     * @var SalesDocument
     */
    protected $document;

    /**
     * List of penalties for applying discounts.
     *
     * @var ComisionPenalizacion[]
     */
    protected $penalties = [];

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        if (false === property_exists($doc, 'totalcomision')) {
            return true;
        }

        $this->document = $doc;
        $this->loadCommissions();
        $this->loadPenalties();

        $totalcommission = 0.0;
        foreach ($lines as $line) {
            if (false === $line->suplido) {
                $totalcommission += $this->recalculateLine($line);
            }
        }

        $doc->totalcomision = round($totalcommission, (int)FS_NF0);
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        return true;
    }

    /**
     * Gets the commission percentage for the document line.
     *
     * @param SalesDocumentLine $line
     * @return float
     */
    protected function getCommission($line)
    {
        $product = $line->getProducto();
        foreach ($this->commissions as $commission) {
            if ($this->isValidCommissionForLine($line, $product, $commission)) {
                if ($commission->porcentaje == 0.00 || $line->dtopor == 0.00) {
                    return $commission->porcentaje;
                }

                $result = $commission->porcentaje - $this->getPenalty($line->dtopor);
                if ($result < 0.00) {
                    $result = 0.00;
                }
                return $result;
            }
        }

        return 0.0;
    }

    /**
     * Gets the penalty for the commission if the sale has been discounted.
     *
     * @param float $discount
     * @return float
     */
    protected function getPenalty($discount)
    {
        foreach ($this->penalties as $penalty) {
            if ($this->isValidPenaltyForDiscount($penalty, $discount)) {
                return $penalty->penalizacion;
            }
        }

        return 0.00;
    }

    /**
     * Check if the commission record is applicable to the document
     *
     * @param Comision $commission
     *
     * @return bool
     */
    protected function isValidCommissionForDoc($commission): bool
    {
        if (!empty($commission->codagente) && $commission->codagente != $this->document->codagente) {
            return false;
        }

        if (!empty($commission->codcliente) && $commission->codcliente != $this->document->codcliente) {
            return false;
        }

        return true;
    }

    /**
     * Check if the commission record is applicable to the line document
     *
     * @param SalesDocumentLine $line
     * @param Producto $product
     * @param Comision $commission
     *
     * @return bool
     */
    protected function isValidCommissionForLine(&$line, $product, $commission): bool
    {
        if (!empty($commission->codfamilia) && $commission->codfamilia != $product->codfamilia) {
            return false;
        }

        if (!empty($commission->idproducto) && $commission->idproducto != $line->idproducto) {
            return false;
        }

        return true;
    }

    /**
     * Check if the penalty record is applicable to the line document
     *
     * @param CommissionPenalty $penalty
     * @param float $discount
     * @return bool
     */
    protected function isValidPenaltyForDiscount($penalty, $discount): bool
    {
        if (!empty($penalty->idempresa) && $penalty->idempresa != $this->document->idempresa) {
            return false;
        }

        if ($discount > $penalty->dto_desde) {
            return false;
        }
        return true;
    }

    /**
     * Charge applicable commissions.
     */
    protected function loadCommissions()
    {
        $this->commissions = [];
        if (empty($this->document->codagente)) {
            return;
        }

        $commission = new Comision();
        $where = [new DataBaseWhere('idempresa', $this->document->idempresa)];
        foreach ($commission->all($where, ['prioridad' => 'DESC'], 0, 0) as $comm) {
            if ($this->isValidCommissionForDoc($comm)) {
                $this->commissions[] = $comm;
            }
        }
    }

    /**
     * Charge applicable penalties.
     */
    protected function loadPenalties()
    {
        if (empty($this->commissions)) {
            return;
        }

        $model = new ComisionPenalizacion();
        $where = [
            new DataBaseWhere('codagente', $this->document->codagente),
            new DataBaseWhere('idempresa', $this->document->idempresa),
            new DataBaseWhere('idempresa', null, 'IS', 'OR')
        ];

        $order = [
            'COALESCE(idempresa, 9999999)' => 'ASC',
            'dto_desde' => 'ASC'
        ];

        foreach ($model->all($where, $order, 0, 0) as $penalty) {
            $this->penalties[] = $penalty;
        }
    }

    /**
     * Update commission sale of a document line
     *
     * @param SalesDocumentLine $line
     * @return float
     */
    protected function recalculateLine(&$line)
    {
        $newValue = $this->getCommission($line);
        if ($newValue != $line->porcomision && $line->primaryColumnValue()) {
            $line->porcomision = $newValue;
            $line->save();
        }

        return $line->porcomision * $line->pvptotal / 100;
    }
}
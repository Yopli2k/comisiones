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

namespace FacturaScripts\Plugins\Comisiones\Mod;

use Exception;
use FacturaScripts\Core\Contract\CalculatorModInterface;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Comision;
use FacturaScripts\Dinamic\Model\ComisionPenalizacion;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Description of CalculatorMod
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class CalculatorMod implements CalculatorModInterface
{
    /**
     * Commission ratio.
     *
     * @var Comision[]
     */
    protected $commissions = [];

    /**
     * List of penalties for applying discounts.
     *
     * @var ComisionPenalizacion[]
     */
    protected $penalties = [];

    /**
     * Settlement associated with the document.
     *
     * @var LiquidacionComision
     */
    protected $settlement;

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        if ($doc instanceof SalesDocument) {
            // cargamos comisiones y penalizaciones aplicables
            $this->loadCommissions($doc->idempresa, $doc->codagente, $doc->codcliente);
            $this->loadPenalties($doc->idempresa, $doc->codagente);
            // cargamos la liquidación del documento
            if ($doc->hasColumn('idliquidacion')) {
                $this->settlement = new LiquidacionComision();
                $this->settlement->load($doc->idliquidacion);
            }
        }
        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        if (false === $doc->hasColumn('totalcomision')) {
            // si no existe el campo totalcomision, no se calcula nada
            return true;
        }

        if ($this->isInvoiced($doc)) {
            // si ya hay una liquidación facturada, no se calcula la comisión
            return true;
        }

        // calculamos el total de comisiones
        $totalCommission = 0.0;
        foreach ($lines as $line) {
            $totalCommission += $line->porcomision * $line->pvptotal / 100.0;
        }

        $decimals = Tools::settings('default', 'decimals', 2);
        $doc->totalcomision = round($totalCommission, $decimals);

        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        if (false === $line->hasColumn('porcomision')) {
            // si no hay porcomision, no hay comisiones
            return true;
        }
        if ($this->isInvoiced($doc)) {
            // si ya hay una liquidación facturada, no se calcula la comisión
            return true;
        }

        // calculamos el porcentaje de comisión
        $line->porcomision = $line->suplido ? 0.0 : $this->getCommission($line);

        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        if (false === $doc->hasColumn('totalcomision')) {
            // si no hay totalcomision, no hay nada que limpiar
            return true;
        }
        if ($this->isInvoiced($doc)) {
            // si ya hay una liquidación facturada, no se calcula la comisión
            return true;
        }

        $doc->totalcomision = 0.0;
        foreach ($lines as $line) {
            $line->porcomision = 0.0;
        }
        return true;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        return true;
    }

    protected function getCommission(SalesDocumentLine $line): float
    {
        $product = $line->getProducto();
        foreach ($this->commissions as $commission) {
            if (false === $this->isValidCommissionForLine($line, $product, $commission)) {
                continue;
            }

            // si no hay descuento, no hace falta buscar penalizaciones
            if ($commission->porcentaje == 0.00 || $line->dtopor == 0.00) {
                return $commission->porcentaje;
            }

            // si hay descuento, buscamos penalizaciones
            $result = $commission->porcentaje - $this->getPenalty($line->dtopor);
            if ($result < 0.00) {
                $result = 0.00;
            }
            return $result;
        }

        return 0.0;
    }

    protected function getPenalty(float $discount): float
    {
        foreach ($this->penalties as $penalty) {
            if ($discount >= $penalty->dto_desde && $discount <= $penalty->dto_hasta) {
                return $penalty->penalizacion;
            }
        }

        return 0.00;
    }

    protected function isValidCommissionForDoc(Comision $commission, string $codagente, string $codcliente): bool
    {
        // comprobamos el agente si la comision tiene uno asignado
        if (false === empty($commission->codagente) && $commission->codagente != $codagente) {
            return false;
        }

        // comprobamos el cliente si la comision tiene uno asignado
        if (false === empty($commission->codcliente) && $commission->codcliente != $codcliente) {
            return false;
        }

        return true;
    }

    protected function isValidCommissionForLine(SalesDocumentLine &$line, Producto $product, Comision $commission): bool
    {
        // comprobamos la familia del producto
        if (!empty($commission->codfamilia) && $commission->codfamilia != $product->codfamilia) {
            return false;
        }

        // comprobamos el producto
        if (!empty($commission->idproducto) && $commission->idproducto != $line->idproducto) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function loadCommissions(int $idempresa, ?string $codagente, string $codcliente): void
    {
        $this->commissions = [];
        if (empty($codagente)) {
            return;
        }

        $where = [
            Where::column('idempresa', $idempresa),
            Where::sub([
                Where::column('codagente', $codagente),
                Where::column('codagente', null, 'IS', 'OR'),
            ]),
        ];
        foreach (Comision::all($where, ['prioridad' => 'DESC']) as $comm) {
            if ($this->isValidCommissionForDoc($comm, $codagente, $codcliente)) {
                $this->commissions[] = $comm;
            }
        }
    }

    protected function loadPenalties(int $idempresa, ?string $codagente): void
    {
        $this->penalties = [];
        if (empty($this->commissions)) {
            return;
        }

        $where = [
            Where::sub([
                Where::column('codagente', $codagente),
                Where::column('codagente', null, 'IS', 'OR'),
            ]),
            Where::sub([
                Where::column('idempresa', $idempresa),
                Where::column('idempresa', null, 'IS', 'OR'),
            ]),
        ];
        $order = [
            'COALESCE(idempresa, 9999999)' => 'ASC',
            'COALESCE(codagente, \'zzzzzz\')' => 'ASC',
            'dto_desde' => 'ASC'
        ];
        foreach (ComisionPenalizacion::all($where, $order) as $penalty) {
            $this->penalties[] = $penalty;
        }
    }

    private function isInvoiced(BusinessDocument &$doc): bool
    {
        return $doc->hasColumn('idliquidacion')
            && isset($this->settlement)
            && !empty($this->settlement->idfactura);
    }
}

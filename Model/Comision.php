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

namespace FacturaScripts\Plugins\Comisiones\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Agente as DinAgente;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * List of a sellers commissions.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class Comision extends ModelClass
{
    use ModelTrait;

    /**
     * code of agent.
     *
     * @var string
     */
    public $codagente;

    /**
     * code of customer.
     *
     * @var string
     */
    public $codcliente;

    /**
     * code of family.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Primary Key
     *
     * @var int
     */
    public $idcomision;

    /**
     * Link to company model
     *
     * @var int
     */
    public $idempresa;

    /**
     * code of product.
     *
     * @var int
     */
    public $idproducto;

    /**
     * Commission percentage.
     *
     * @var float
     */
    public $porcentaje;

    /**
     *
     * @var int
     */
    public $prioridad;

    public function clear(): void
    {
        parent::clear();
        $this->porcentaje = 0.00;
        $this->prioridad = 0;
    }

    public function install(): string
    {
        new DinAgente();
        new DinCliente();
        new DinProducto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idcomision';
    }

    public static function tableName(): string
    {
        return 'comisiones';
    }

    public function test(): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}

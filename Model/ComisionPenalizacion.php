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

namespace FacturaScripts\Plugins\Comisiones\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Agente;

/**
 * A penalization to commission for apply discount.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ComisionPenalizacion extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Link to the agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Link to company model.
     *
     * @var integer
     */
    public $idempresa;

    /**
     * from % discount.
     *
     * @var float
     */
    public $dto_desde;

    /**
     * up to % discount.
     *
     * @var float
     */
    public $dto_hasta;

    /**
     * penalty percentage
     *
     * @var float
     */
    public $penalizacion;

    public function clear()
    {
        parent::clear();
        $this->dto_desde = 1.00;
        $this->dto_hasta = 100.00;
        $this->penalizacion = 100.00;
    }

    public function install(): string
    {
        new Agente();
        parent::install();

        return '';
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'comisionespenalizaciones';
    }

    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List'): string
    {
        return parent::url($type, $list);
    }

}

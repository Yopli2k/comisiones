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
namespace FacturaScripts\PLugins\Comisiones\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Agente as DinAgente;

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

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->dto_desde = 1.00;
        $this->dto_hasta = 100.00;
        $this->penalizacion = 100.00;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new DinAgente();
        parent::install();

        return '';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'comisionespenalizaciones';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List')
    {
        return parent::url($type, $list);
    }

}

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

namespace FacturaScripts\Plugins\Comisiones\Model\Join;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Description of SettledReceipt
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 *
 * @property int $idfactura
 */
class LiquidacionComisionFactura extends JoinModel
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new FacturaCliente());
    }

    /**
     * Add to the indicated settlement the list of customer invoices
     * according to the where filter.
     *
     * @param int $settled
     * @param DataBaseWhere[] $where
     */
    public function addInvoiceToSettle($settled, $where)
    {
        $where[] = new DataBaseWhere('facturascli.idliquidacion', null, 'IS');
        $invoices = $this->all($where);
        if (empty($invoices)) {
            return;
        }

        $sql = 'UPDATE ' . FacturaCliente::tableName()
            . ' SET idliquidacion = ' . self::$dataBase->var2str($settled)
            . ' WHERE ' . FacturaCliente::primaryColumn() . ' = ';

        self::$dataBase->beginTransaction();

        try {
            foreach ($invoices as $row) {
                $idinvoice = self::$dataBase->var2str($row->idfactura);
                self::$dataBase->exec($sql . $idinvoice);
            }
            self::$dataBase->commit();
        } catch (Exception $exc) {
            self::$dataBase->rollback();
            Tools::log()->error($exc->getMessage());
        }
    }

    public function delete(): bool
    {
        $sql = 'UPDATE ' . FacturaCliente::tableName() . ' SET idliquidacion = NULL'
            . ' WHERE ' . FacturaCliente::primaryColumn() . ' = ' . self::$dataBase->var2str($this->idfactura);
        return self::$dataBase->exec($sql);
    }

    /**
     * Get value from modal view cursor of the master model primary key.
     *
     * @return int
     */
    public function primaryColumnValue()
    {
        return $this->idfactura;
    }

    protected function getFields(): array
    {
        return [
            'codagente' => 'facturascli.codagente',
            'codcliente' => 'facturascli.codcliente',
            'codejercicio' => 'facturascli.codejercicio',
            'codigo' => 'facturascli.codigo',
            'codpago' => 'facturascli.codpago',
            'codserie' => 'facturascli.codserie',
            'fecha' => 'facturascli.fecha',
            'hora' => 'facturascli.hora',
            'idempresa' => 'facturascli.idempresa',
            'idfactura' => 'facturascli.idfactura',
            'idliquidacion' => 'facturascli.idliquidacion',
            'nombrecliente' => 'facturascli.nombrecliente',
            'numero' => 'facturascli.numero',
            'numero2' => 'facturascli.numero2',
            'neto' => 'facturascli.neto',
            'pagada' => 'facturascli.pagada',
            'total' => 'facturascli.total',
            'totalcomision' => 'facturascli.totalcomision'
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'facturascli INNER JOIN formaspago ON formaspago.codpago = facturascli.codpago';
    }

    protected function getTables(): array
    {
        return ['facturascli', 'formaspago'];
    }
}

<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts.
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Plugins\Comisiones\Model\Comision;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ComisionTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCreateToAgent(): void
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

    public function testOrder(): void
    {
        // creamos un agente
        $agent = new Agente();
        $agent->nombre = 'Test Order';
        $this->assertTrue($agent->save());

        // creamos una comisión para el agente
        $com1 = new Comision();
        $com1->codagente = $agent->codagente;
        $com1->porcentaje = 5;
        $this->assertTrue($com1->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido asociado al agente y cliente
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea al pedido
        $line = $order->getNewLine();
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha calculado la comisión correctamente
        $this->assertEquals(50, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($agent->delete());
    }

    public function testOrderWithoutAgent(): void
    {
        // creamos un agente con comisión
        $agent = new Agente();
        $agent->nombre = 'Test No Agent';
        $this->assertTrue($agent->save());

        $com1 = new Comision();
        $com1->codagente = $agent->codagente;
        $com1->porcentaje = 10;
        $this->assertTrue($com1->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido SIN agente asignado
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        // NO asignamos codagente
        $this->assertTrue($order->save());

        // añadimos una línea al pedido
        $line = $order->getNewLine();
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que NO se ha calculado comisión
        $this->assertEquals(0, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($agent->delete());
    }

    public function testOrderWithSpecificAgent(): void
    {
        // creamos varios agentes con diferentes comisiones
        $agent1 = new Agente();
        $agent1->nombre = 'Agent 1';
        $this->assertTrue($agent1->save());

        $com1 = new Comision();
        $com1->codagente = $agent1->codagente;
        $com1->porcentaje = 10;
        $this->assertTrue($com1->save());

        $agent2 = new Agente();
        $agent2->nombre = 'Agent 2';
        $this->assertTrue($agent2->save());

        $com2 = new Comision();
        $com2->codagente = $agent2->codagente;
        $com2->porcentaje = 20;
        $this->assertTrue($com2->save());

        $agent3 = new Agente();
        $agent3->nombre = 'Agent 3';
        $this->assertTrue($agent3->save());

        $com3 = new Comision();
        $com3->codagente = $agent3->codagente;
        $com3->porcentaje = 15;
        $this->assertTrue($com3->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido y asignamos solo el agente 2
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent2->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea al pedido
        $line = $order->getNewLine();
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha calculado la comisión del agente 2 (20% de 1000 = 200)
        // y NO de los otros agentes
        $this->assertEquals(200, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($agent1->delete());
        $this->assertTrue($agent2->delete());
        $this->assertTrue($agent3->delete());
    }

    public function testOrderWithMultipleCommissions(): void
    {
        // creamos un agente
        $agent = new Agente();
        $agent->nombre = 'Agent Priority';
        $this->assertTrue($agent->save());

        // creamos varias comisiones para el mismo agente con diferentes prioridades
        // comisión con prioridad baja
        $com1 = new Comision();
        $com1->codagente = $agent->codagente;
        $com1->porcentaje = 5;
        $com1->prioridad = 1;
        $this->assertTrue($com1->save());

        // comisión con prioridad alta (esta debe aplicarse)
        $com2 = new Comision();
        $com2->codagente = $agent->codagente;
        $com2->porcentaje = 15;
        $com2->prioridad = 10;
        $this->assertTrue($com2->save());

        // comisión con prioridad media
        $com3 = new Comision();
        $com3->codagente = $agent->codagente;
        $com3->porcentaje = 8;
        $com3->prioridad = 5;
        $this->assertTrue($com3->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido con el agente
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea al pedido
        $line = $order->getNewLine();
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha aplicado la comisión con mayor prioridad
        // prioridad 10 = 15% de 1000 = 150
        $this->assertEquals(150, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($agent->delete());
    }

    public function testOrderWithProductCommission(): void
    {
        // creamos un agente
        $agent = new Agente();
        $agent->nombre = 'Agent Product';
        $this->assertTrue($agent->save());

        // comisión genérica del agente
        $comAgent = new Comision();
        $comAgent->codagente = $agent->codagente;
        $comAgent->porcentaje = 10;
        $comAgent->prioridad = 1;
        $this->assertTrue($comAgent->save());

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        // comisión específica para el producto (con mayor prioridad)
        $comProduct = new Comision();
        $comProduct->codagente = $agent->codagente;
        $comProduct->idproducto = $product->idproducto;
        $comProduct->porcentaje = 25;
        $comProduct->prioridad = 10;
        $this->assertTrue($comProduct->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido con el agente
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea con el producto específico
        $line = $order->getNewLine();
        $line->idproducto = $product->idproducto;
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha aplicado la comisión del producto (25% de 1000 = 250)
        // y NO la comisión genérica del agente (10% = 100)
        $this->assertEquals(250, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($agent->delete());
    }

    public function testOrderWithGeneralCommission(): void
    {
        // creamos una comisión general (sin agente específico) para todos los agentes
        $comGeneral = new Comision();
        // NO asignamos codagente, por lo tanto aplica a todos
        $comGeneral->porcentaje = 12;
        $comGeneral->prioridad = 5;
        $this->assertTrue($comGeneral->save());

        // creamos un agente SIN comisiones específicas
        $agent = new Agente();
        $agent->nombre = 'Agent Without Commission';
        $this->assertTrue($agent->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido con el agente
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea al pedido
        $line = $order->getNewLine();
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha aplicado la comisión general (12% de 1000 = 120)
        // aunque el agente no tenga comisiones específicas
        $this->assertEquals(120, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($agent->delete());
        $this->assertTrue($comGeneral->delete());
    }

    public function testOrderWithFamilyCommission(): void
    {
        // creamos un agente
        $agent = new Agente();
        $agent->nombre = 'Agent Family';
        $this->assertTrue($agent->save());

        // comisión genérica del agente con prioridad baja
        $comAgent = new Comision();
        $comAgent->codagente = $agent->codagente;
        $comAgent->porcentaje = 8;
        $comAgent->prioridad = 1;
        $this->assertTrue($comAgent->save());

        // creamos una familia
        $family = new Familia();
        $family->codfamilia = 'TFAM';
        $family->descripcion = 'Test Family';
        $this->assertTrue($family->save());

        // comisión específica para la familia con prioridad alta
        $comFamily = new Comision();
        $comFamily->codagente = $agent->codagente;
        $comFamily->codfamilia = $family->codfamilia;
        $comFamily->porcentaje = 18;
        $comFamily->prioridad = 10;
        $this->assertTrue($comFamily->save());

        // creamos un producto con esa familia
        $product = $this->getRandomProduct();
        $product->codfamilia = $family->codfamilia;
        $this->assertTrue($product->save());

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos un pedido con el agente
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($customer));
        $order->codagente = $agent->codagente;
        $this->assertTrue($order->save());

        // añadimos una línea con el producto de la familia
        $line = $order->getNewLine();
        $line->idproducto = $product->idproducto;
        $line->cantidad = 10;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // actualizamos el pedido
        $lines = $order->getLines();
        $this->assertTrue(Calculator::calculate($order, $lines, true));

        // comprobamos que se ha aplicado la comisión de la familia (18% de 1000 = 180)
        // y NO la comisión genérica del agente (8% = 80)
        $this->assertEquals(180, $order->totalcomision);

        // eliminamos
        $this->assertTrue($order->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($family->delete());
        $this->assertTrue($agent->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}

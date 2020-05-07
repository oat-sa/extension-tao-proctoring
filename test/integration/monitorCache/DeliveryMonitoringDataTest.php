<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\test\integration\monitorCache;

require_once dirname(__FILE__).'/../../../../tao/includes/raw_start.php';

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionContextInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\generis\test\MockObject;

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryMonitoringDataTest extends TaoPhpUnitTestRunner
{
    /**
     * @var DeliveryExecutionInterface|MockObject
     */
    private $deliveryExecutionMock;

    /**
     * @var DeliveryExecutionContextInterface|MockObject
     */
    private $deliveryExecutionContextMock;

    /**
     * @var DeliveryMonitoringData
     */
    private $object;

    public function setUp(): void
    {
        parent::setUp();

        $this->deliveryExecutionContextMock = $this->createMock(DeliveryExecutionContextInterface::class);
        $this->deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);
        $this->object = new DeliveryMonitoringData($this->deliveryExecutionMock, []);
    }

    public function testConstruct()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474_test_record';
        $columns = [
            DeliveryMonitoringService::TEST_TAKER=> 'http://sample/first.rdf#superUser',
            DeliveryMonitoringService::STATUS => 'initial',
            'arbitrary_key' => 'arbitrary_value',
        ];

        $deliveryExecution = $this->getDeliveryExecution();

        $data = new DeliveryMonitoringData($deliveryExecution, [DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]);
        $data->setServiceLocator($this->getServiceManagerProphecy());
        foreach ($columns as $columnKey => $columnVal) {
            $data->addValue($columnKey, $columnVal);
        }

        foreach ($columns as $columnKey => $columnVal) {
            $this->assertNotEmpty($data->get()[$columnKey]);
            $this->assertEquals($data->get()[$columnKey], $columnVal);
        }
    }

    public function testAddValue()
    {
        $deliveryExecution = $this->getDeliveryExecution();
        $data = new DeliveryMonitoringData($deliveryExecution, [DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]);
        $data->setServiceLocator($this->getServiceManagerProphecy());

        $this->assertEquals($data->get()[DeliveryMonitoringService::DELIVERY_EXECUTION_ID], $deliveryExecution->getIdentifier());

        $data->addValue('new_value', 'value');
        $this->assertEquals($data->get()['new_value'], 'value');

        //should not be overwritten
        $data->addValue('new_value', 'value_changed');
        $this->assertEquals($data->get()['new_value'], 'value');

        //should be overwritten
        $data->addValue('new_value', 'value_changed', true);
        $this->assertEquals($data->get()['new_value'], 'value_changed');
    }

    public function testValidate()
    {
        $deliveryExecution = $this->getDeliveryExecution();
        $data = new DeliveryMonitoringData($deliveryExecution, [DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]);
        $data->setServiceLocator($this->getServiceManagerProphecy());
        $this->assertFalse($data->validate());
        $errors = $data->getErrors();

        $this->assertTrue(!empty($errors));

        $data->addValue(DeliveryMonitoringService::TEST_TAKER, 'test_taker_id');
        $data->addValue(DeliveryMonitoringService::STATUS, 'active');

        $this->assertTrue($data->validate());
        $errors = $data->getErrors();
        $this->assertTrue(empty($errors));
    }

    private function getDeliveryExecution($state = null)
    {
        $id = 'http://sample/first.rdf#i1450190828500474_test_record';
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize(DeliveryExecution::class);
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        $deliveryExecutionProphecy->getState()->willReturn($state);
        return $deliveryExecutionProphecy->reveal();
    }

    public function testGetDeliveryExecution()
    {
        $deliveryExecution = $this->object->getDeliveryExecution();

        $this->assertInstanceOf(DeliveryExecutionInterface::class, $deliveryExecution, 'Method must return an instance of expected interface.');
    }

    public function testSetDeliveryExecutionContext()
    {
        $this->deliveryExecutionContextMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn([]);

        $this->object->setDeliveryExecutionContext($this->deliveryExecutionContextMock);

        $monitoringData = $this->object->get();
        $this->assertArrayHasKey(
            'execution_context',
            $monitoringData,
            'Delivery execution context object must be stored in monitoring data.'
        );
        $this->assertIsString(
            $monitoringData['execution_context'],
            'Delivery monitoring context must be stored as JSON string.'
        );
    }

    public function testDetDeliveryExecutionContextDoesNotExist()
    {
        $result = $this->object->getDeliveryExecutionContext();

        $this->assertNull($result, 'Method must return false if context does not exist.');
    }

    public function testGetDeliveryExecutionContext()
    {
        $contextData = [
            'execution_id' => 'http://test-execution-uri.dev',
            'context_id' => 'test_context_id',
        ];
        $this->deliveryExecutionContextMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn($contextData);

        $this->object->setDeliveryExecutionContext($this->deliveryExecutionContextMock);

        $result = $this->object->getDeliveryExecutionContext();

        $this->assertInstanceOf(
            DeliveryExecutionContextInterface::class,
            $result,
            'Method must return an instance of expected interface.'
        );
    }
}
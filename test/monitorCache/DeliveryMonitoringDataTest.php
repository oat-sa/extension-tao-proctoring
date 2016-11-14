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

namespace oat\taoProctoring\test\monitorCache;

use oat\oatbox\service\ServiceManager;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\taoDelivery\model\execution\DeliveryExecution;

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

    public $persistence;

    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
        $this->persistence = \common_persistence_Manager::getPersistence('default');
    }

    /**
     * @after
     * @before
     */
    public function deleteTestData()
    {
        $sql = 'DELETE FROM ' . DeliveryMonitoringService::TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . " LIKE '%_test_record'";

        $this->persistence->exec($sql);
    }

    public function testConstruct()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474_test_record';
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);

        $columns = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'http://sample/first.rdf#superUser',
            DeliveryMonitoringService::COLUMN_STATUS => 'initial',
            'arbitrary_key' => 'arbitrary_value',
        ];

        $deliveryExecution = $this->getDeliveryExecution();

        $data = new DeliveryMonitoringData($deliveryExecution, false);
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
        $data = new DeliveryMonitoringData($deliveryExecution, false);

        $this->assertEquals($data->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], $deliveryExecution->getIdentifier());

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
        $data = new DeliveryMonitoringData($deliveryExecution, false);
        $this->assertFalse($data->validate());
        $errors = $data->getErrors();

        $this->assertTrue(!empty($errors));

        $data->addValue(DeliveryMonitoringService::COLUMN_TEST_TAKER, 'test_taker_id');
        $data->addValue(DeliveryMonitoringService::COLUMN_STATUS, 'active');

        $this->assertTrue($data->validate());
        $errors = $data->getErrors();
        $this->assertTrue(empty($errors));
    }

    private function getDeliveryExecution()
    {
        $id = 'http://sample/first.rdf#i1450190828500474_test_record';
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize(DeliveryExecution::class);
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        return $deliveryExecutionProphecy->reveal();
    }
}
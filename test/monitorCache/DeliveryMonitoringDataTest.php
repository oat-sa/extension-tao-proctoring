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

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;

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
        $service = new DeliveryMonitoringService(array(DeliveryMonitoringService::OPTION_PERSISTENCE => 'default'));

        $columns = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'http://sample/first.rdf#superUser',
            DeliveryMonitoringService::COLUMN_STATUS => 'initial',
            'arbitrary_key' => 'arbitrary_value',
        ];

        $data = new DeliveryMonitoringData($deliveryExecutionId);
        foreach ($columns as $columnKey => $columnVal) {
            $data->add($columnKey, $columnVal);
        }

        $this->assertTrue($service->save($data));

        $createdData = new DeliveryMonitoringData($deliveryExecutionId);

        foreach ($columns as $columnKey => $columnVal) {
            $this->assertNotEmpty($createdData->get()[$columnKey]);
            $this->assertEquals($createdData->get()[$columnKey], $columnVal);
        }
    }

    public function testAdd()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474_test_record';
        $data = new DeliveryMonitoringData($deliveryExecutionId);

        $this->assertEquals($data->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], $deliveryExecutionId);

        $data->add('new_value', 'value');
        $this->assertEquals($data->get()['new_value'], 'value');

        //should not be overwritten
        $data->add('new_value', 'value_changed');
        $this->assertEquals($data->get()['new_value'], 'value');

        //should be overwritten
        $data->add('new_value', 'value_changed', true);
        $this->assertEquals($data->get()['new_value'], 'value_changed');
    }

    public function testSet()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474_test_record';

        $newData = [
            DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450190828500475_test_record',
            'new_value' => 'value',
        ];

        $data = new DeliveryMonitoringData($deliveryExecutionId);
        $data->set($newData);

        $this->assertEquals($data->get(), $newData);
    }

    public function testValidate()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474_test_record';
        $data = new DeliveryMonitoringData($deliveryExecutionId);
        $this->assertFalse($data->validate());
        $errors = $data->getErrors();

        $this->assertTrue(!empty($errors));

        $data->add(DeliveryMonitoringService::COLUMN_TEST_TAKER, 'test_taker_id');
        $data->add(DeliveryMonitoringService::COLUMN_STATUS, 'active');

        $this->assertTrue($data->validate());
        $errors = $data->getErrors();
        $this->assertTrue(empty($errors));
    }
}
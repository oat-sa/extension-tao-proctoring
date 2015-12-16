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
    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
    }

    public function testAdd()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474';
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
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474';

        $newData = [
            DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450190828500475',
            'new_value' => 'value',
        ];

        $data = new DeliveryMonitoringData($deliveryExecutionId);
        $data->set($newData);

        $this->assertEquals($data->get(), $newData);
    }

    public function testValidate()
    {
        $deliveryExecutionId = 'http://sample/first.rdf#i1450190828500474';
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
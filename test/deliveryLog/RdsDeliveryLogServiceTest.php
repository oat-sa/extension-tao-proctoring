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
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class RdsDeliveryLogServiceTest extends TaoPhpUnitTestRunner
{
    /**
     * @var RdsDeliveryLogService
     */
    private $service;
    private $persistence;
    private $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554180_test_record';

    /**
     * Set up test
     */
    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
        $this->service = new RdsDeliveryLogService(array(RdsDeliveryLogService::OPTION_PERSISTENCE => 'default'));
        $this->persistence = \common_persistence_Manager::getPersistence('default');
    }

    public function tearDown()
    {
        $this->deleteTestData();
    }

    /**
     * Clear test data before and after each test method
     * @after
     * @before
     */
    public function deleteTestData()
    {
        $sql = 'DELETE FROM ' . RdsDeliveryLogService::TABLE_NAME .
            ' WHERE ' . RdsDeliveryLogService::DELIVERY_EXECUTION_ID . " LIKE '%_test_record'";

        $this->persistence->exec($sql);
    }

    /**
     * @dataProvider getLogData
     * @param $deliveryExecutionId
     * @param $eventId
     * @param $data
     * @param string $userId
     */
    public function testLog($deliveryExecutionId, $eventId, $data, $userId)
    {
        $result = $this->service->log($deliveryExecutionId, $eventId, $data, $userId);
        $this->assertTrue($result);

        $loggedData = $this->getRecordByDeliveryExecutionId($deliveryExecutionId);

        //get last logged record
        $loggedData = $loggedData[count($loggedData) - 1];

        $this->assertEquals($deliveryExecutionId, $loggedData[RdsDeliveryLogService::DELIVERY_EXECUTION_ID]);
        $this->assertEquals($eventId, $loggedData[RdsDeliveryLogService::EVENT_ID]);
        $this->assertEquals($data, json_decode($loggedData[RdsDeliveryLogService::DATA], true));
        $this->assertTrue(is_numeric($loggedData[RdsDeliveryLogService::CREATED_AT]));
        $this->assertTrue(!empty($loggedData[RdsDeliveryLogService::CREATED_BY]));

        if ($userId) {
            $this->assertEquals($userId, $loggedData[RdsDeliveryLogService::CREATED_BY]);
        }
    }


    /**
     * @dataProvider getLogData
     * @param $deliveryExecutionId
     * @param $eventId
     * @param $data
     */
    public function testGet($deliveryExecutionId, $eventId, $data)
    {
        $this->service->log($deliveryExecutionId, $eventId, $data);
        $this->service->log($deliveryExecutionId, 'test', 'test_val');

        $loggedData = $this->service->get($deliveryExecutionId, $eventId);
        $loggedDataAllEvents = $this->service->get($deliveryExecutionId);

        $this->assertEquals(1, count($loggedData));
        $this->assertEquals(2, count($loggedDataAllEvents));

        //get last logged record
        $loggedData = $loggedData[count($loggedData) - 1];

        $this->assertEquals($deliveryExecutionId, $loggedData[RdsDeliveryLogService::DELIVERY_EXECUTION_ID]);
        $this->assertEquals($eventId, $loggedData[RdsDeliveryLogService::EVENT_ID]);
        $this->assertEquals($data, $loggedData[RdsDeliveryLogService::DATA]);
        $this->assertTrue(is_numeric($loggedData[RdsDeliveryLogService::CREATED_AT]));
        $this->assertTrue(!empty($loggedData[RdsDeliveryLogService::CREATED_BY]));
    }

    /**
     * Get data to be logged
     * @return array
     */
    public function getLogData()
    {
        return [
            [
                $this->deliveryExecutionId,
                'test_event_1',
                'string value',
                'user_id'
            ],
            [
                $this->deliveryExecutionId,
                'test_event_2',
                [
                    'key_1' => 'value_1',
                    'key_2' => 'value_2',
                ],
                null
            ],
        ];
    }

    /**
     * Get logged data from database
     * @param $id delivery execution id
     * @return array
     */
    private function getRecordByDeliveryExecutionId($id)
    {
        $sql = 'SELECT * FROM ' . RdsDeliveryLogService::TABLE_NAME .
            ' WHERE ' . RdsDeliveryLogService::DELIVERY_EXECUTION_ID . '=?';

        return $this->persistence->query($sql, [$id])->fetchAll();
    }
}
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
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
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
        $this->service = new RdsDeliveryLogService(array(RdsDeliveryLogService::OPTION_PERSISTENCE => 'default',
            'fields' => array(
                'event_id',
                'created_by',
                'delivery_execution_id'
            )));
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

        // @todo fix "Base table or view not found: 1146 Table 'tao.delivery_log' doesn't exist"
        $this->persistence->exec($sql);
    }

    /**
     * @dataProvider getLogData
     * @param $deliveryExecutionId
     */
    public function testSearch($deliveryExecutionId)
    {
        $this->service->log($deliveryExecutionId, 'test_event_same_uniq_1', 'test_val_1');
        $this->service->log($deliveryExecutionId, 'test_event_same_uniq_1', 'test_val_2');

        $loggedData = $this->service->search([
            'event_id' => 'test_event_same_uniq_1',
        ], [
            'order' => 'created_at',
            'dir' => 'desc',
        ]);

        $this->assertEquals(2, count($loggedData));

        $firstLog = $loggedData[0];
        $this->assertEquals($deliveryExecutionId, $firstLog[RdsDeliveryLogService::DELIVERY_EXECUTION_ID]);
        $this->assertEquals('test_event_same_uniq_1', $firstLog[RdsDeliveryLogService::EVENT_ID]);
        $this->assertEquals('test_val_2', $firstLog[RdsDeliveryLogService::DATA]);

        $secondLog = $loggedData[1];
        $this->assertEquals($deliveryExecutionId, $secondLog[RdsDeliveryLogService::DELIVERY_EXECUTION_ID]);
        $this->assertEquals('test_event_same_uniq_1', $secondLog[RdsDeliveryLogService::EVENT_ID]);
        $this->assertEquals('test_val_1', $secondLog[RdsDeliveryLogService::DATA]);
    }

    /**
     * @dataProvider getLogData
     * @param $deliveryExecutionId
     */
    public function testDelete($deliveryExecutionId)
    {
        $this->service->log($deliveryExecutionId, 'test_event_same_uniq_2', 'test_val_1');

        $executionMock = $this->getMockBuilder(DeliveryExecutionInterface::class)->getMock();
        $executionMock
            ->method('getIdentifier')
            ->willReturn($deliveryExecutionId);

        $request = $this->getMockBuilder(DeliveryExecutionDeleteRequest::class)->disableOriginalConstructor()->getMock();
        $request
            ->method('getDeliveryExecution')
            ->willReturn($executionMock);

        $this->assertTrue($this->service->deleteDeliveryExecutionData($request));
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
     * @param string $id delivery execution id
     * @return array
     */
    private function getRecordByDeliveryExecutionId($id)
    {
        $sql = 'SELECT * FROM ' . RdsDeliveryLogService::TABLE_NAME .
            ' WHERE ' . RdsDeliveryLogService::DELIVERY_EXECUTION_ID . '=?';

        return $this->persistence->query($sql, [$id])->fetchAll();
    }
}
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
class DeliveryMonitoringServiceTest extends TaoPhpUnitTestRunner
{
    /**
     * @var DeliveryMonitoringService
     */
    private $service;
    private $persistence;
    private $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554175_test';

    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
        $this->service = new DeliveryMonitoringService();
        $this->persistence = \common_persistence_Manager::getPersistence('default');
    }

    public function tearDown()
    {
        $sql = 'DELETE FROM ' . DeliveryMonitoringService::TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . ' = ' . $this->deliveryExecutionId;

        $this->persistence->exec($sql, $this->createdRecords);
    }

    public function testSave()
    {
        $data = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_id',
            DeliveryMonitoringService::COLUMN_STATUS => 'active',
        ];

        $dataModel = new DeliveryMonitoringData($this->deliveryExecutionId);

        //data is now valid
        $this->assertFalse($this->service->save($dataModel));
        //$data->validate() has been called
        $this->assertNotEmpty($dataModel->getErrors());

        foreach ($data as $key => $val) {
            $dataModel->add($key, $val);
        }

        $this->assertTrue($this->service->save($dataModel));
        $this->assertEmpty($dataModel->getErrors());

        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);

        $this->assertNotEmpty($insertedData);

        foreach ($data as $key => $val) {
            $this->assertEquals($insertedData[0][$key], $val);
        }
    }

    private function getRecordByDeliveryExecutionId($id)
    {
        $sql = 'SELECT * FROM ' . DeliveryMonitoringService::TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '=?';

        return $this->persistence->query($sql, [$this->deliveryExecutionId])->fetchAll();
    }
}
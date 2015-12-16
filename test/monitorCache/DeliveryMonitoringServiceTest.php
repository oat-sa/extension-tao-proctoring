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
        $this->deleteTestData();
    }

    /**
     * @after
     */
    public function deleteTestData()
    {
        $sql = 'DELETE FROM ' . DeliveryMonitoringService::TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . ' = ?';

        $this->persistence->exec($sql, [$this->deliveryExecutionId]);
    }

    public function testSave()
    {
        $data = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_id',
            DeliveryMonitoringService::COLUMN_STATUS => 'active',
        ];

        $secondaryData = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $dataModel = new DeliveryMonitoringData($this->deliveryExecutionId);

        //data is now valid
        $this->assertFalse($this->service->save($dataModel));
        //$data->validate() has been called
        $this->assertNotEmpty($dataModel->getErrors());

        foreach ($data as $key => $val) {
            $dataModel->add($key, $val);
        }

        foreach ($secondaryData as $secKey => $secVal) {
            $dataModel->add($secKey, $secVal);
        }

        $this->assertTrue($this->service->save($dataModel));
        $this->assertEmpty($dataModel->getErrors());

        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);

        $this->assertNotEmpty($insertedData);

        foreach ($data as $key => $val) {
            $this->assertEquals($insertedData[0][$key], $val);
        }

        $insertedKvData = $this->getKvRecordsByParentId($insertedData[0]['id']);

        $this->assertNotEmpty($insertedKvData);
        $this->assertEquals(count($insertedKvData), count($secondaryData));

        foreach ($insertedKvData as $kvData) {
            $key = $kvData[DeliveryMonitoringService::KV_COLUMN_KEY];
            $val = $kvData[DeliveryMonitoringService::KV_COLUMN_VALUE];
            $this->assertTrue(isset($secondaryData[$key]));
            $this->assertEquals($secondaryData[$key], $val);
        }
    }


    public function testDelete()
    {
        $data = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_id',
            DeliveryMonitoringService::COLUMN_STATUS => 'active',
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $dataModel = new DeliveryMonitoringData($this->deliveryExecutionId);

        foreach ($data as $key => $val) {
            $dataModel->add($key, $val);
        }

        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertNotEmpty($insertedData);

        $this->assertTrue($this->service->delete($dataModel));

        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertEmpty($insertedData);
    }


    /**
     * @dataProvider loadFixture
     */
    public function testFind($data)
    {
        var_dump($data);
        $this->assertTrue(true);
    }

    public function loadFixture()
    {
        //TODO LOAD data
        return [
            [1]
        ];
    }

    private function getRecordByDeliveryExecutionId($id)
    {
        $sql = 'SELECT * FROM ' . DeliveryMonitoringService::TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '=?';

        return $this->persistence->query($sql, [$id])->fetchAll();
    }

    private function getKvRecordsByParentId($parentId)
    {
        $sql = 'SELECT * FROM ' . DeliveryMonitoringService::KV_TABLE_NAME .
            ' WHERE ' . DeliveryMonitoringService::KV_COLUMN_PARENT_ID . '=?';

        return $this->persistence->query($sql, [$parentId])->fetchAll();
    }
}
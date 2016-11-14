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
use oat\taoProctoring\test\monitorCache\mock\DeliveryMonitoringData;
use oat\oatbox\service\ServiceManager;
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
    protected $service;
    protected $persistence;
    protected $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554175_test_record';

    public function setUp()
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');

        TaoPhpUnitTestRunner::initTest();

        $this->service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $this->persistence = \common_persistence_Manager::getPersistence('default');
    }

    public function tearDown()
    {
        $this->deleteTestData();
    }

    /**
     * @after
     * @before
     */
    public function deleteTestData()
    {
        $service = $this->service;

        $sql = 'DELETE FROM ' . $service::TABLE_NAME .
            ' WHERE ' . $service::COLUMN_DELIVERY_EXECUTION_ID . " LIKE '%_test_record'";

        $this->persistence->exec($sql);
    }

    public function testSave()
    {
        $service = $this->service;
        $data = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_id',
            DeliveryMonitoringService::COLUMN_STATUS => 'active',
        ];

        $secondaryData = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $deliveryExecution = $this->getDeliveryExecution();
        $dataModel = new DeliveryMonitoringData($deliveryExecution);

        //data is not valid
        $this->assertFalse($this->service->save($dataModel));
        //$data->validate() has been called
        $this->assertNotEmpty($dataModel->getErrors());

        foreach ($data as $key => $val) {
            $dataModel->addValue($key, $val);
        }

        foreach ($secondaryData as $secKey => $secVal) {
            $dataModel->addValue($secKey, $secVal);
        }

        $this->assertTrue($this->service->save($dataModel));
        $this->assertEmpty($dataModel->getErrors());


        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertNotEmpty($insertedData);
        //one row has been inserted
        $this->assertEquals(count($insertedData), 1);
        $this->assertEquals($insertedData[0][DeliveryMonitoringService::COLUMN_STATUS], 'active');

        foreach ($data as $key => $val) {
            $this->assertEquals($insertedData[0][$key], $val);
        }

        $insertedKvData = $this->getKvRecordsByParentId($insertedData[0][$service::COLUMN_ID]);

        $this->assertNotEmpty($insertedKvData);
        $this->assertEquals(count($insertedKvData), count($secondaryData));

        foreach ($insertedKvData as $kvData) {
            $key = $kvData[DeliveryMonitoringService::KV_COLUMN_KEY];
            $val = $kvData[DeliveryMonitoringService::KV_COLUMN_VALUE];
            $this->assertTrue(isset($secondaryData[$key]));
            $this->assertEquals($secondaryData[$key], $val);
        }


        $dataModel->addValue(DeliveryMonitoringService::COLUMN_STATUS, 'finished', true);
        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        //new row has not been inserted
        $this->assertEquals(count($insertedData), 1);
        $this->assertEquals($insertedData[0][DeliveryMonitoringService::COLUMN_STATUS], 'finished');

        //update record in kv table
        $dataModel->addValue('secondary_data_key', 'new value', true);
        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getKvRecordsByParentId($this->deliveryExecutionId);
        $this->assertTrue(
            in_array([
                DeliveryMonitoringService::KV_COLUMN_PARENT_ID => $this->deliveryExecutionId,
                DeliveryMonitoringService::KV_COLUMN_KEY => 'secondary_data_key',
                DeliveryMonitoringService::KV_COLUMN_VALUE => 'new value'
            ], $insertedData)
        );
    }


    public function testDelete()
    {
        $data = [
            DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_id',
            DeliveryMonitoringService::COLUMN_STATUS => 'active',
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $dataModel = new DeliveryMonitoringData($this->getDeliveryExecution());

        foreach ($data as $key => $val) {
            $dataModel->addValue($key, $val);
        }

        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertNotEmpty($insertedData);

        $this->assertTrue($this->service->delete($dataModel));

        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertEmpty($insertedData);
    }


    public function testFind()
    {
        $this->loadFixture();

        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record']
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554175_test_record');

        $result = $this->service->find([
            ['error_code' => '1'],
            'OR',
            ['error_code' => '2'],
        ]);
        $this->assertEquals(count($result), 2);

        $result = $this->service->find([
            ['error_code' => '1'],
            'AND',
            ['session_id' => 'i1450191587554175'],
        ]);
        $this->assertEquals(count($result), 1);

        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '1'],
        ]);
        $this->assertEquals(count($result), 0);


        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_STATUS => 'finished_test'],
            'AND',
            [['error_code' => '0'], 'OR', ['error_code' => '1']],
        ]);
        $this->assertEquals(count($result), 1);


        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '0'],
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554178_test_record');


        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '0'],
        ], [], true);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554178_test_record');
        $this->assertEquals($result[0]->get()['error_code'], '0');


        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_STATUS => 'finished_test'],
        ], [], true);
        $this->assertEquals(count($result), 2);

        foreach ($result as $resultRow) {
            $this->assertTrue(isset($resultRow->get()['error_code']));
            $this->assertTrue(isset($resultRow->get()['session_id']));
        }

        $result = $this->service->find([
            ['error_code' => '>=0'],
        ], ['order' => 'error_code ASC, session_id'], true);

        $this->assertEquals(count($result), 4);

        foreach ($result as $rowKey => $resultRow) {
            $this->assertEquals($rowKey, $resultRow->get()['error_code']);
        }


        $result = $this->service->find([
            [DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => [
                'http://sample/first.rdf#i1450191587554175_test_record',
                'http://sample/first.rdf#i1450191587554176_test_record',
                'http://sample/first.rdf#i1450191587554177_test_record'
            ]],
        ], ['order' => 'session_id'], true);
        $this->assertEquals(count($result), 3);
        $this->assertEquals($result[0]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554175_test_record');
        $this->assertEquals($result[1]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554176_test_record');
        $this->assertEquals($result[2]->get()[DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554177_test_record');
    }

    protected function loadFixture()
    {
        $this->setUp();

        $data = [
            [
                DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record',
                DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_1',
                DeliveryMonitoringService::COLUMN_STATUS => 'active_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554175_test_user',
                'error_code' => 1,
                'session_id' => 'i1450191587554175',
            ],
            [
                DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554176_test_record',
                DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_2',
                DeliveryMonitoringService::COLUMN_STATUS => 'paused_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554176_test_user',
                'error_code' => 2,
                'session_id' => 'i1450191587554176',
            ],
            [
                DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554177_test_record',
                DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_3',
                DeliveryMonitoringService::COLUMN_STATUS => 'finished_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554177_test_user',
                'error_code' => 3,
                'session_id' => 'i1450191587554177',
            ],
            [
                DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554178_test_record',
                DeliveryMonitoringService::COLUMN_TEST_TAKER => 'test_taker_4',
                DeliveryMonitoringService::COLUMN_STATUS => 'finished_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554178_test_user',
                'error_code' => 0,
                'session_id' => 'i1450191587554178',
            ],
        ];

        foreach ($data as $item) {
            $dataModel = new DeliveryMonitoringData($this->getDeliveryExecution($item[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]));
            foreach ($item as $key => $val) {
                $dataModel->addValue($key, $val);
            }
            $this->service->save($dataModel);
        }

        return [
            [$data],
        ];
    }

    protected function getRecordByDeliveryExecutionId($id)
    {
        $service = $this->service;
        $sql = 'SELECT * FROM ' . $service::TABLE_NAME .
            ' WHERE ' . $service::COLUMN_DELIVERY_EXECUTION_ID . '=?';

        return $this->persistence->query($sql, [$id])->fetchAll();
    }

    protected function getKvRecordsByParentId($parentId)
    {
        $service = $this->service;
        $sql = 'SELECT * FROM ' . $service::KV_TABLE_NAME .
            ' WHERE ' . $service::KV_COLUMN_PARENT_ID . '=?';

        return $this->persistence->query($sql, [$parentId])->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getDeliveryExecution($id = null)
    {
        if ($id === null) {
            $id = $this->deliveryExecutionId;
        }
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize('oat\taoDelivery\models\classes\execution\DeliveryExecution');
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        return $deliveryExecutionProphecy->reveal();
    }
}

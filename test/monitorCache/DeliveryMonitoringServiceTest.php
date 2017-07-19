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
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

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
     * @var MonitoringStorage
     */
    protected $service;
    protected $persistence;
    protected $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554175_test_record';

    public function setUp()
    {
        parent::setUp();
        TaoPhpUnitTestRunner::initTest();

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');

        $this->service = new MonitoringStorage([
            MonitoringStorage::OPTION_PERSISTENCE => 'test_monitoring',
            MonitoringStorage::OPTION_PRIMARY_COLUMNS => array(
                'delivery_execution_id',
                'status',
                'current_assessment_item',
                'test_taker',
                'authorized_by',
                'start_time',
                'end_time'
            )
        ]);
        $this->persistence = \common_persistence_Manager::getPersistence('default');
        $this->service->setServiceLocator($this->getServiceManagerProphecy());

        $pmMock = $this->getSqlMock('test_monitoring');
        $this->persistence = $pmMock->getPersistenceById('test_monitoring');
        DbSetup::generateTable($this->persistence);

        $config = $this->prophesize(\common_persistence_KeyValuePersistence::class);
        $config->get(\common_persistence_Manager::SERVICE_ID)->willReturn($pmMock);
        $config->get(DeliveryMonitoringService::SERVICE_ID)->willReturn($this->service);
        $this->service->setServiceLocator(new ServiceManager($config->reveal()));
    }

    public function tearDown()
    {
        parent::tearDown();
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
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitoringStorage::COLUMN_STATUS => 'active',
        ];

        $secondaryData = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $deliveryExecution = $this->getDeliveryExecution();
        $dataModel = $this->service->getData($deliveryExecution);

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
        $this->assertEquals(1, count($insertedData));
        $this->assertEquals('active', $insertedData[0][MonitoringStorage::COLUMN_STATUS]);

        foreach ($data as $key => $val) {
            $this->assertEquals($insertedData[0][$key], $val);
        }

        $insertedKvData = $this->getKvRecordsByParentId($insertedData[0][$service::COLUMN_ID]);

        $this->assertNotEmpty($insertedKvData);
        $this->assertEquals(count($secondaryData), count($insertedKvData));

        foreach ($insertedKvData as $kvData) {
            $key = $kvData[MonitoringStorage::KV_COLUMN_KEY];
            $val = $kvData[MonitoringStorage::KV_COLUMN_VALUE];
            $this->assertTrue(isset($secondaryData[$key]));
            $this->assertEquals($secondaryData[$key], $val);
        }


        $dataModel->addValue(MonitoringStorage::COLUMN_STATUS, 'finished', true);
        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        //new row has not been inserted
        $this->assertEquals(count($insertedData), 1);
        $this->assertEquals($insertedData[0][MonitoringStorage::COLUMN_STATUS], 'finished');

        //update record in kv table
        $dataModel->addValue('secondary_data_key', 'new value', true);
        $this->assertTrue($this->service->save($dataModel));
        $insertedData = $this->getKvRecordsByParentId($this->deliveryExecutionId);
        $this->assertTrue(
            in_array([
                MonitoringStorage::KV_COLUMN_PARENT_ID => $this->deliveryExecutionId,
                MonitoringStorage::KV_COLUMN_KEY => 'secondary_data_key',
                MonitoringStorage::KV_COLUMN_VALUE => 'new value'
            ], $insertedData)
        );
    }


    public function testDelete()
    {
        $data = [
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitoringStorage::COLUMN_STATUS => 'active',
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];

        $dataModel = $this->service->getData($this->getDeliveryExecution());

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
            [MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record']
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554175_test_record');

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
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '1'],
        ]);
        $this->assertEquals(count($result), 0);


        $result = $this->service->find([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            'AND',
            [['error_code' => '0'], 'OR', ['error_code' => '1']],
        ]);
        $this->assertEquals(count($result), 1);


        $result = $this->service->find([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '0'],
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554178_test_record');


        $result = $this->service->find([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '0'],
        ], [], true);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554178_test_record');
        $this->assertEquals($result[0]->get()['error_code'], '0');


        $result = $this->service->find([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
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
            [MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => [
                'http://sample/first.rdf#i1450191587554175_test_record',
                'http://sample/first.rdf#i1450191587554176_test_record',
                'http://sample/first.rdf#i1450191587554177_test_record'
            ]],
        ], ['order' => 'session_id'], true);
        $this->assertEquals(count($result), 3);
        $this->assertEquals($result[0]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554175_test_record');
        $this->assertEquals($result[1]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554176_test_record');
        $this->assertEquals($result[2]->get()[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID], 'http://sample/first.rdf#i1450191587554177_test_record');
    }

    public function testCount()
    {
        $this->loadFixture();

        $result = $this->service->count();
        $this->assertEquals(4, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record']
        ]);
        $this->assertEquals(1, $result);


        $result = $this->service->count([
            ['error_code' => '1'],
            'OR',
            ['error_code' => '2'],
        ]);
        $this->assertEquals(2, $result);


        $result = $this->service->count([
            ['error_code' => '1'],
            'AND',
            ['session_id' => 'i1450191587554175'],
        ]);
        $this->assertEquals(1, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '1'],
        ]);
        $this->assertEquals(0, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            'AND',
            [['error_code' => '0'], 'OR', ['error_code' => '1']],
        ]);
        $this->assertEquals(1, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
            ['error_code' => '0'],
        ]);
        $this->assertEquals(1, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_STATUS => 'finished_test'],
        ]);
        $this->assertEquals(2, $result);


        $result = $this->service->count([
            ['error_code' => '>=0'],
        ]);
        $this->assertEquals(4, $result);


        $result = $this->service->count([
            [MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => [
                'http://sample/first.rdf#i1450191587554175_test_record',
                'http://sample/first.rdf#i1450191587554176_test_record',
                'http://sample/first.rdf#i1450191587554177_test_record'
            ]],
        ]);
        $this->assertEquals(3, $result);
    }

    protected function loadFixture()
    {
        $this->setUp();

        $data = [
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_1',
                MonitoringStorage::COLUMN_STATUS => 'active_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554175_test_user',
                'error_code' => 1,
                'session_id' => 'i1450191587554175',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554176_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_2',
                MonitoringStorage::COLUMN_STATUS => 'paused_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554176_test_user',
                'error_code' => 2,
                'session_id' => 'i1450191587554176',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554177_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_3',
                MonitoringStorage::COLUMN_STATUS => 'finished_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554177_test_user',
                'error_code' => 3,
                'session_id' => 'i1450191587554177',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554178_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_4',
                MonitoringStorage::COLUMN_STATUS => 'finished_test',
                PROPERTY_DELVIERYEXECUTION_SUBJECT => 'http://sample/first.rdf#i1450191587554178_test_user',
                'error_code' => 0,
                'session_id' => 'i1450191587554178',
            ],
        ];

        foreach ($data as $item) {
            $dataModel = $this->service->getData($this->getDeliveryExecution($item[MonitoringStorage::DELIVERY_EXECUTION_ID]));
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
        $deliveryExecutionProphecy = $prophet->prophesize('oat\taoDelivery\model\execution\DeliveryExecution');
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        return $deliveryExecutionProphecy->reveal();
    }
}

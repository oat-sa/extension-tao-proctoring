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
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorInterface;

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

        $pmMock = $this->getSqlMock('test_monitoring');
        $this->persistence = $pmMock->getPersistenceById('test_monitoring');
        DbSetup::generateTable($this->persistence);

        $sl = $this->prophesize(ServiceLocatorInterface::class);
        $sl->get(\common_persistence_Manager::SERVICE_ID)->willReturn($pmMock);
        $this->service->setServiceLocator($sl->reveal());
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

    public function testPartialSave()
    {
        // 1. create regular cache record
        $dataToUpdate = [
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitoringStorage::COLUMN_STATUS => 'active',
        ];
        $secondaryDataToUpdate = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];
        $dataToCheck = $dataToUpdate;
        $secondaryDataToCheck = $secondaryDataToUpdate;

        $this->save(false, false, $dataToUpdate, $secondaryDataToUpdate, $dataToCheck, $secondaryDataToCheck);

        // 2. update partially

        $dataToCheck = $dataToUpdate;
        $secondaryDataToCheck = $secondaryDataToUpdate;
        $dataToUpdate = [
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id_STEP_2',
        ];
        $dataToCheck[MonitoringStorage::COLUMN_TEST_TAKER] = 'test_taker_id_STEP_2';
        $secondaryDataToUpdate = [
            'secondary_data_key_2' => 'secondary_data_val_2_STEP_2',
        ];
        $secondaryDataToCheck['secondary_data_key_2'] = 'secondary_data_val_2_STEP_2';


        $this->save(true, true, $dataToUpdate, $secondaryDataToUpdate, $dataToCheck, $secondaryDataToCheck);
    }

    public function testSaveFallbackAfterInsertFailed()
    {
        // 1. create regular cache record
        $dataToUpdate = [
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitoringStorage::COLUMN_STATUS => 'active',
        ];
        $secondaryDataToUpdate = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];
        $dataToCheck = $dataToUpdate;
        $secondaryDataToCheck = $secondaryDataToUpdate;

        $this->save(false, false, $dataToUpdate, $secondaryDataToUpdate, $dataToCheck, $secondaryDataToCheck);

        // 2. check fallback of partialSave() (insert fails, update works)

        $dataToCheck = $dataToUpdate;
        $dataToUpdate[MonitoringStorage::COLUMN_TEST_TAKER] = 'test_taker_id_STEP_2';
        $dataToCheck[MonitoringStorage::COLUMN_TEST_TAKER] = 'test_taker_id_STEP_2';
        $secondaryDataToUpdate['secondary_data_key_2'] = 'secondary_data_val_2_STEP_2';
        $secondaryDataToCheck['secondary_data_key_2'] = 'secondary_data_val_2_STEP_2';

        $this->save(false, true, $dataToUpdate, $secondaryDataToUpdate, $dataToCheck, $secondaryDataToCheck);
    }

    public function testSaveFallbackAfterUpdateReturns0Rows()
    {
        // 1. create regular cache record
        $dataToUpdate = [
            MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitoringStorage::COLUMN_STATUS => 'active',
        ];
        $secondaryDataToUpdate = [
            'secondary_data_key' => 'secondary_data_val',
            'secondary_data_key_2' => 'secondary_data_val_2',
        ];
        $dataToCheck = $dataToUpdate;
        $secondaryDataToCheck = $secondaryDataToUpdate;

        $this->save(false, true, $dataToUpdate, $secondaryDataToUpdate, $dataToCheck, $secondaryDataToCheck);
    }

    /**
     * @param $partialModel
     * @param $saveAsPartial
     * @param array $dataToUpdate
     * @param array $secondaryDataToUpdate
     * @param array $dataToCheck
     * @param array $secondaryDataToCheck
     * @throws \common_exception_NotFound
     */
    protected function save($partialModel, $saveAsPartial, array $dataToUpdate, array $secondaryDataToUpdate, array $dataToCheck, array $secondaryDataToCheck)
    {
        $deliveryExecution = $this->getDeliveryExecution($this->deliveryExecutionId, 'active');
        if ($partialModel) {
            $dataModel = $this->service->createMonitoringData($deliveryExecution);
        } else {
            $dataModel = $this->service->getData($deliveryExecution);
        }

        foreach ($dataToUpdate as $key => $val) {
            $dataModel->addValue($key, $val, true);
        }
        foreach ($secondaryDataToUpdate as $secKey => $secVal) {
            $dataModel->addValue($secKey, $secVal, true);
        }

        if ($saveAsPartial) {
            $saveResult = $this->service->partialSave($dataModel);
        } else {
            $saveResult = $this->service->save($dataModel);
        }

        $this->assertTrue($saveResult);
        $this->assertEmpty($dataModel->getErrors());


        $insertedData = $this->getRecordByDeliveryExecutionId($this->deliveryExecutionId);
        $this->assertNotEmpty($insertedData);
        $this->assertEquals(1, count($insertedData));
        $this->assertEquals('active', $insertedData[0][MonitoringStorage::COLUMN_STATUS]);

        foreach ($dataToCheck as $key => $val) {
            $this->assertEquals($insertedData[0][$key], $val);
        }

        // check key value data
        $service = $this->service;
        $insertedKvData = $this->getKvRecordsByParentId($insertedData[0][$service::COLUMN_ID]);

        $this->assertNotEmpty($insertedKvData);

        $insertedKvDataNotEmptyFieldCount = 0;
        foreach ($insertedKvData as $fieldData) {
            $fieldValue = $fieldData[MonitoringStorage::KV_COLUMN_VALUE];
            if ($fieldValue !== null) {
                $insertedKvDataNotEmptyFieldCount++;
            }
        }

        $this->assertEquals(count($secondaryDataToCheck), $insertedKvDataNotEmptyFieldCount);

        foreach ($insertedKvData as $kvData) {
            $key = $kvData[MonitoringStorage::KV_COLUMN_KEY];
            $val = $kvData[MonitoringStorage::KV_COLUMN_VALUE];
            if ($val !== null) {
                $this->assertTrue(isset($secondaryDataToCheck[$key]));
                $this->assertEquals($secondaryDataToCheck[$key], $val);
            }
        }
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
                OntologyDeliveryExecution::PROPERTY_SUBJECT => 'http://sample/first.rdf#i1450191587554175_test_user',
                'error_code' => 1,
                'session_id' => 'i1450191587554175',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554176_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_2',
                MonitoringStorage::COLUMN_STATUS => 'paused_test',
                OntologyDeliveryExecution::PROPERTY_SUBJECT => 'http://sample/first.rdf#i1450191587554176_test_user',
                'error_code' => 2,
                'session_id' => 'i1450191587554176',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554177_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_3',
                MonitoringStorage::COLUMN_STATUS => 'finished_test',
                OntologyDeliveryExecution::PROPERTY_SUBJECT => 'http://sample/first.rdf#i1450191587554177_test_user',
                'error_code' => 3,
                'session_id' => 'i1450191587554177',
            ],
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554178_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_4',
                MonitoringStorage::COLUMN_STATUS => 'finished_test',
                OntologyDeliveryExecution::PROPERTY_SUBJECT => 'http://sample/first.rdf#i1450191587554178_test_user',
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

    protected function getDeliveryExecution($id = null, $state = null)
    {
        if ($id === null) {
            $id = $this->deliveryExecutionId;
        }
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize('oat\taoDelivery\model\execution\DeliveryExecution');
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);

        $stateProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $stateProphecy->getUri()->willReturn($state);

        $deliveryExecutionProphecy->getState()->willReturn($stateProphecy);
        return $deliveryExecutionProphecy->reveal();
    }
}

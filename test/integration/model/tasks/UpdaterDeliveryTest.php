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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoProctoring\test\integration\model\authorization;

require_once dirname(__FILE__).'/../../../../../tao/includes/raw_start.php';

use oat\oatbox\service\ServiceManager;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\model\Tasks\DeliveryUpdaterTask;
use oat\taoProctoring\scripts\install\db\DbSetup;


/**
 * Test the UpdaterDeliveryTest
 *
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class UpdaterDeliveryTest extends TaoPhpUnitTestRunner
{
    /**
     * @var MonitoringStorage
     */
    protected $service;

    /**
     * @var DeliveryUpdaterTask
     */
    protected $deliveryUpdaterTask;

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
                'end_time',
                'delivery_name',
                'delivery_id'
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

        $this->deliveryUpdaterTask = new DeliveryUpdaterTask();
        $this->deliveryUpdaterTask->setServiceLocator($this->service->getServiceLocator());
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

    /**
     * Test the ProctorAuthorizationProvider API
     */
    public function testUpdateDeliveryLabels()
    {
        $this->loadFixture();
        $update = $this->deliveryUpdaterTask->updateDeliveryLabels('http://sample/first.rdf#i1450191587554180_test_record', 'Delivery test 2');
        $this->assertTrue($update);
        $result = $this->service->find([
            [MonitoringStorage::DELIVERY_ID => 'http://sample/first.rdf#i1450191587554180_test_record'],
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[MonitoringStorage::DELIVERY_NAME], 'Delivery test 2', 1);
    }

    /**
     * @return array
     */
    protected function loadFixture()
    {
        $this->setUp();

        $data = [
            [
                MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID => 'http://sample/first.rdf#i1450191587554175_test_record',
                MonitoringStorage::COLUMN_TEST_TAKER => 'test_taker_1',
                MonitoringStorage::COLUMN_STATUS => 'active_test',
                OntologyDeliveryExecution::PROPERTY_SUBJECT => 'http://sample/first.rdf#i1450191587554175_test_user',
                MonitoringStorage::DELIVERY_NAME => 'Delivery test 1',
                MonitoringStorage::DELIVERY_ID => 'http://sample/first.rdf#i1450191587554180_test_record',


            ]
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

    /**
     * @param null $id
     * @return object
     */
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

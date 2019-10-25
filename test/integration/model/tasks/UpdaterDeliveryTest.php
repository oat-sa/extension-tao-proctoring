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

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use Prophecy\Argument;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\model\Tasks\DeliveryUpdaterTask;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\generis\test\TestCase;


/**
 * Test the UpdaterDeliveryTest
 *
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class UpdaterDeliveryTest extends TestCase
{
    /**
     * @var MonitoringStorage
     */
    protected $deliveryMonitoringService;

    /**
     * @var DeliveryUpdaterTask
     */
    protected $deliveryUpdaterTask;

    protected $persistence;

    protected $pmMock;

    /** @var string  */
    protected $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554175_test_record';

    /**
     * Test the UpdateDelivery task for updating labels
     */
    public function testUpdateDeliveryLabels()
    {
        $this->loadFixture();

        $update = $this->getDeliveryUpdaterTask()->updateDeliveryLabels('http://sample/first.rdf#i1450191587554180_test_record', 'Delivery test 2');
        $this->assertTrue($update);

        $result = $this->getDeliveryMonitoringService()->find([
            [MonitoringStorage::DELIVERY_ID => 'http://sample/first.rdf#i1450191587554180_test_record'],
        ]);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->get()[MonitoringStorage::DELIVERY_NAME], 'Delivery test 2', 1);
    }

    /**
     * Load fixtures for delivery monitoring table
     * @return array
     */
    protected function loadFixture()
    {
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
            $dataModel = $this->getDeliveryMonitoringService()->getData($this->getDeliveryExecution($item[MonitoringStorage::DELIVERY_EXECUTION_ID]));
            foreach ($item as $key => $val) {
                $dataModel->addValue($key, $val);
            }
            $this->getDeliveryMonitoringService()->save($dataModel);
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
        $deliveryExecutionProphecy = $prophet->prophesize(DeliveryExecution::class);
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);

        $stateProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $stateProphecy->getUri()->willReturn(DeliveryExecution::STATE_PAUSED);
        $deliveryExecutionProphecy->getState()->willReturn($stateProphecy);

        return $deliveryExecutionProphecy->reveal();
    }

    /**
     * Init Persistence with mock.
     */
    protected function initPersistence()
    {
        $this->pmMock = $this->getSqlMock('test_monitoring');
        $this->persistence = $this->pmMock->getPersistenceById('test_monitoring');
        DbSetup::generateTable($this->persistence);

    }

    /**
     * Init DeliveryMonitoring Service
     */
    protected function initDeliveryMonitoringService()
    {
        $this->deliveryMonitoringService = new MonitoringStorage([
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

        $this->initPersistence();
        $config = $this->prophesize(\common_persistence_KeyValuePersistence::class);
        $config->get(\common_persistence_Manager::SERVICE_ID)->willReturn($this->pmMock);
        $config->get(DeliveryMonitoringService::SERVICE_ID)->willReturn($this->deliveryMonitoringService);
        $this->deliveryMonitoringService->setServiceLocator(new ServiceManager($config->reveal()));
    }

    /**
     * Init DeliveryUpdater task object
     */
    protected function initDeliveryUpdaterTask()
    {
        $this->deliveryUpdaterTask = new DeliveryUpdaterTask();
        $this->deliveryUpdaterTask->setServiceLocator($this->getDeliveryMonitoringService()->getServiceLocator());
    }

    /**
     * Get DeliveryMonitoringService object
     *
     * @return MonitoringStorage
     */
    protected function getDeliveryMonitoringService()
    {
        if (!$this->deliveryMonitoringService) {
            $this->initDeliveryMonitoringService();
        }
        return $this->deliveryMonitoringService;
    }

    /**
     * Get DeliveryUpdaterTask object
     *
     * @return DeliveryUpdaterTask
     */
    protected function getDeliveryUpdaterTask()
    {
        if (!$this->deliveryUpdaterTask) {
            $this->initDeliveryUpdaterTask();
        }
        return $this->deliveryUpdaterTask;
    }

}

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

namespace oat\taoProctoring\model\monitorCache\update;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringDataInterface;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringServiceInterface;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TestUpdate implements DeliveryMonitoringDataInterface
{
    private $serviceCallId;
    
    private $description;

    public function __construct($serviceCallId, $description)
    {
        $this->serviceCallId = $serviceCallId;
        $this->description = $description;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringDataInterface::set()
     */
    public function set(array $data)
    {
        throw new \common_exception_NotImplemented(__CLASS__.'::'.__FUNCTION__);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringDataInterface::get()
     */
    public function get()
    {
        return array(
            DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => $this->serviceCallId,
            DeliveryMonitoringService::COLUMN_STATUS => $this->description
        );
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringDataInterface::validate()
     */
    public function validate()
    {
        return true;
    }

    public static function testStateChange(TestChangedEvent $event)
    {
        //$update = new self($event->getServiceCallId(), $event->getNewStateDescription());
        
        $update = new DeliveryMonitoringData($event->getServiceCallId());
        $dataArray = $update->get();
        if (!isset($dataArray[DeliveryMonitoringService::COLUMN_TEST_TAKER])) {
            \common_Logger::i('Retrieving test-taker id for monitoring');
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
            $update->add(DeliveryMonitoringService::COLUMN_TEST_TAKER, $deliveryExecution->getUserIdentifier());
        }
        $update->add(DeliveryMonitoringService::COLUMN_STATUS, $event->getNewStateDescription(), true);
        
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringServiceInterface::CONFIG_ID);
        $success = $service->save($update);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }
}
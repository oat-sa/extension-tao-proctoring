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

namespace oat\taoProctoring\model\monitorCache\implementation;

use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\tao\model\event\MetadataModified;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoProctoring\model\monitorCache\update\DeliveryUpdate;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;

/**
 * Class MonitorCacheService
 *
 * RDS Implementation of DeliveryMonitoringService without using autoincrement fields and PDO::lastInsertId function.
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class MonitorCacheService extends MonitoringStorage
{

    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();
        $data = $this->getData($deliveryExecution);
        $data->update(DeliveryMonitoringService::STATUS, $deliveryExecution->getState()->getUri());
        $data->update(DeliveryMonitoringService::TEST_TAKER, $deliveryExecution->getUserIdentifier());
        // need to add user to event
        $data->update(DeliveryMonitoringService::TEST_TAKER_FIRST_NAME, '');
        $data->update(DeliveryMonitoringService::TEST_TAKER_LAST_NAME, '');

        $data->update(DeliveryMonitoringService::DELIVERY_ID, $deliveryExecution->getDelivery()->getUri());
        $data->update(DeliveryMonitoringService::DELIVERY_NAME, $deliveryExecution->getDelivery()->getLabel());
        $data->update(DeliveryMonitoringService::START_TIME, $deliveryExecution->getStartTime());
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    
    }
    
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $data = $this->getData($event->getDeliveryExecution());
        $data->update(DeliveryMonitoringService::STATUS, $event->getState());
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $event->getDeliveryExecution()->getIdentifier() . ' could not be created');
        }
    }

    public function testStateChanged(QtiTestChangeEvent $event)
    {
        $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
        $data = $this->getData($deliveryExecution);
        $data->setTestSession($event->getSession());
        $data->update(DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM, $event->getNewStateDescription());
        $data->updateData([
            DeliveryMonitoringService::END_TIME,
            DeliveryMonitoringService::REMAINING_TIME,
            DeliveryMonitoringService::EXTRA_TIME,
        ]);
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }

    /**
     * Update the label of the delivery across the entrie cache
     *
     * @param MetadataModified $event
     */
    public function deliverylabelChanged(MetadataModified $event)
    {
        $resource = $event->getResource();
        if ($event->getMetadataUri() === RDFS_LABEL) {
            $assemblyClass = DeliveryAssemblyService::singleton()->getRootClass();
            if ($resource->isInstanceOf($assemblyClass)) {
                $update = new DeliveryUpdater();
                $update->changeLabel($this, $resource->getUri(), $event->getMetadataValue());
            }
        }
    }
}

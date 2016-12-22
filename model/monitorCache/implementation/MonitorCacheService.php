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
        $data->update(DeliveryMonitoringService::STATUS, $deliveryExecution->getState(), true);
        $data->update(DeliveryMonitoringService::TEST_TAKER, $deliveryExecution->getUserIdentifier(), true);
        $data->update(DeliveryMonitoringService::DELIVERY_ID, $deliveryExecution->getDelivery()->getUri(), true);
        $data->update(DeliveryMonitoringService::DELIVERY_NAME, $deliveryExecution->getDelivery()->getLabel(), true);
        $data->update(DeliveryMonitoringService::START_TIME, $deliveryExecution->getStartTime(), true);
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    
    }
    
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        /** @var DeliveryMonitoringService $service */
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $data = $this->getData($event->getDeliveryExecution());
        $data->update(DeliveryMonitoringService::STATUS, $event->getState());
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    }
}

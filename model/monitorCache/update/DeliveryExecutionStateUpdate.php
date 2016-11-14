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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoProctoring\model\monitorCache\update;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
/**
 *
 * @package oat\taoProctoring
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */
class DeliveryExecutionStateUpdate
{

    public static function stateChange(DeliveryExecutionState $event)
    {
        /** @var DeliveryMonitoringService $service */
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $deliveryExecution = $event->getDeliveryExecution();

        $data = $service->getData($deliveryExecution, true);
        
        $success = $service->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    }


}
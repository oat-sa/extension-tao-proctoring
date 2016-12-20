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
 **/

namespace oat\taoProctoring\model\monitorCache\update;

use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\event\EligiblityChanged;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use taoDelivery_models_classes_execution_ServiceProxy;

/**
 *
 * @package oat\taoProctoring
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */
class EligiblityUpdate
{

    public static function eligiblityChange(EligiblityChanged $event)
    {
        $serviceManager = ServiceManager::getServiceManager();
        /** @var DeliveryMonitoringService $monitoringService */
        $monitoringService = $serviceManager->get(DeliveryMonitoringService::CONFIG_ID);

        $eligiblity = $event->getEligiblity();

        $before = array_map([__CLASS__, 'getNormalizedItem'], $event->getPreviousTestTakerCollection());
        $after = array_map([__CLASS__, 'getNormalizedItem'], $event->getActualTestTakersCollection());

        $newTestTakers = array_diff($after, $before);

        $delivery = $serviceManager->get(EligibilityService::SERVICE_ID)->getDelivery($eligiblity);

        //might be we would like to remove newly uneliglbe executions later
        foreach ($newTestTakers as $testTakerUri) {
            $executions = taoDelivery_models_classes_execution_ServiceProxy::singleton()->getUserExecutions($delivery, $testTakerUri);
            foreach ($executions as $execution) {
                $deliverMonitoringData = $monitoringService->getData($execution);
                $deliverMonitoringData->updateData([DeliveryMonitoringService::TEST_CENTER_ID]);
                $monitoringService->save($deliverMonitoringData);
            }
        }

    }

    /***
     * @param core_kernel_classes_Resource|string $item
     * @return string
     */
    private static function getNormalizedItem($item)
    {
        if ($item instanceof \core_kernel_classes_Resource) {
            return $item->getUri();
        }
        return (string)$item;
    }


}
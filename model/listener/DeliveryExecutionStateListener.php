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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoProctoring\model\listener;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\TestSessionService;
use oat\taoTests\models\runner\time\TimePoint;

class DeliveryExecutionStateListener extends ConfigurableService
{
    /**
     * When test session is paused by proctor remaining time is recalculated based on server timer to show
     * more precise value. After receiving pause confirmation from client remaining time will be recalculated
     * to show correct value.
     *
     * @param DeliveryExecutionState $event
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     */
    public function updateRemainingTime(DeliveryExecutionState $event): void
    {
        if ($event->getState() != DeliveryExecution::STATE_PAUSED) {
            return;
        }
        $deliveryExecution = $event->getDeliveryExecution();
        $executionId = $deliveryExecution->getIdentifier();

        $monitoringService = $this->getMonitoringService();
        $data = $monitoringService->getData($deliveryExecution);
        $testSession = $this->getTestSession($deliveryExecution);
        if (empty($testSession)) {
            $this->logWarning(
                'monitor cache for delivery ' . $executionId
                    . ' could not be updated. Test session could not be retrieved'
            );

            return;
        }
        $testSession->setTimerTarget(TimePoint::TARGET_SERVER);
        $data->setTestSession($testSession);
        $data->updateData([DeliveryMonitoringService::REMAINING_TIME]);

        $success = $monitoringService->save($data);
        if (!$success) {
            $this->logError(
                'Monitor cache for delivery ' . $executionId . ' could not be updated. Remaining time was not updated'
            );
        }
    }

    /**
     * @param $deliveryExecution
     * @return null|TestSession
     * @throws \common_Exception
     */
    private function getTestSession($deliveryExecution): ?TestSession
    {
        /** @var TestSessionService $testSessionService */
        $testSessionService = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);

        return $testSessionService->getTestSession($deliveryExecution, true);
    }

    /**
     * @return DeliveryMonitoringService
     */
    private function getMonitoringService(): DeliveryMonitoringService
    {
        return $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
    }
}

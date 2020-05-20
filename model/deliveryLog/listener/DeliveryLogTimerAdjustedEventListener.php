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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */
declare(strict_types=1);

namespace oat\taoProctoring\model\deliveryLog\listener;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\event\DeliveryLogEvent;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\ProctoringContextService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use qtism\runtime\tests\AssessmentTestSession;

class DeliveryLogTimerAdjustedEventListener extends ConfigurableService
{
    /**
     * @param DeliveryExecutionTimerAdjusted $event
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     */
    public function adjustTime(DeliveryExecutionTimerAdjusted $event): void
    {
        $data = [
            'reason' => $event->getReason(),
            'increment' => $event->getSeconds(),
            'context' => $this->getContextString()
        ];

        $session = $this->getTestSessionService()->getTestSession($event->getDeliveryExecution());
        if ($session) {
            $data['itemId'] = $this->getCurrentItemId($session);
        }

        $this->getDeliveryLogService()->log(
            $event->getDeliveryExecution()->getIdentifier(),
            DeliveryLogEvent::EVENT_ID_TEST_ADJUST_TIME,
            $data
        );
    }

    /**
     * @return DeliveryLog|object
     */
    private function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * @return TestSessionService|object
     */
    private function getTestSessionService(): TestSessionService
    {
        return $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
    }

    private function getContextString(): string
    {
        return $this->getServiceLocator()->get(ProctoringContextService::class)->getContextString();
    }

    /**
     * @param $session
     * @return string|null
     */
    private function getCurrentItemId(AssessmentTestSession $session): ?string
    {
        $result = null;
        $item = $session->getCurrentAssessmentItemRef();
        if ($item) {
            $result = $item->getIdentifier();
        }
        return $result;
    }
}

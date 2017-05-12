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
 *
 */

namespace oat\taoProctoring\model\implementation;

use DateInterval;
use DateTimeImmutable;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionState;
use oat\taoQtiTest\models\TestSessionService as QtiTestSessionService;
use qtism\runtime\tests\AssessmentTestSession;

/**
 * Interface TestSessionService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TestSessionService extends QtiTestSessionService
{
    const SERVICE_ID = 'taoProctoring/TestSessionService';

    public static function singleton()
    {
        return ServiceManager::getServiceManager()->get(TestSessionService::SERVICE_ID);
    }

    /**
     * Checks if delivery execution was expired after pausing or abandoned after authorization
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function isExpired(DeliveryExecution $deliveryExecution)
    {
        if (!isset(self::$cache[$deliveryExecution->getIdentifier()]['expired'])) {
            $executionState = $deliveryExecution->getState()->getUri();
            if (!in_array($executionState, [
                DeliveryExecutionState::STATE_PAUSED,
                DeliveryExecutionState::STATE_ACTIVE,
                DeliveryExecutionState::STATE_AWAITING,
                DeliveryExecutionState::STATE_AUTHORIZED,
            ]) ||
                !$lastTestTakersEvent = $this->getLastTestTakersEvent($deliveryExecution)) {
                return self::$cache[$deliveryExecution->getIdentifier()]['expired'] = false;
            }

            /** @var \oat\taoProctoring\model\implementation\DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);

            if (($executionState === DeliveryExecutionState::STATE_AUTHORIZED ||
                  $executionState === DeliveryExecutionState::STATE_AWAITING) &&
                $deliveryExecutionStateService->isCancelable($deliveryExecution)) {
                $delay = $deliveryExecutionStateService->getOption(DeliveryExecutionStateService::OPTION_CANCELLATION_DELAY);
                $startedTimestamp = \tao_helpers_Date::getTimeStamp($deliveryExecution->getStartTime(), true);
                $started = (new DateTimeImmutable())->setTimestamp($startedTimestamp);
                if ($started->add(new DateInterval($delay)) < (new DateTimeImmutable())) {
                    self::$cache[$deliveryExecution->getIdentifier()]['expired'] = true;
                    return self::$cache[$deliveryExecution->getIdentifier()]['expired'];
                }
            }

            $wasPausedAt = (new DateTimeImmutable())->setTimestamp($lastTestTakersEvent['created_at']);
            if ($wasPausedAt && $deliveryExecutionStateService->hasOption(DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE)) {
                $delay = $deliveryExecutionStateService->getOption(DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE);
                if ($wasPausedAt->add(new DateInterval($delay)) < (new DateTimeImmutable())) {
                    self::$cache[$deliveryExecution->getIdentifier()]['expired'] = true;

                    return self::$cache[$deliveryExecution->getIdentifier()]['expired'];
                }
            }

            self::$cache[$deliveryExecution->getIdentifier()]['expired'] = false;
        }

        return self::$cache[$deliveryExecution->getIdentifier()]['expired'];
    }

    /**
     * @param AssessmentTestSession $session
     * @return null|string
     */
    public function getProgress(AssessmentTestSession $session = null)
    {
        $result = null;

        if ($session !== null) {
            if ($session->isRunning()) {
                $route = $session->getRoute();
                $currentSection = $session->getCurrentAssessmentSection();
                $sectionItems = $route->getRouteItemsByAssessmentSection($currentSection);
                $currentItem = $route->current();
                $positionInSection = array_search($currentItem, $sectionItems->getArrayCopy(true));

                $result = __('%1$s - item %2$s/%3$s', $currentSection->getTitle(), $positionInSection + 1, count($sectionItems));
            } else {
                $result = __('finished');
            }
        }
        return $result;
    }

    /**
     * Get last test takers event from delivery log
     * @param DeliveryExecution $deliveryExecution
     * @return array|null
     */
    protected function getLastTestTakersEvent(DeliveryExecution $deliveryExecution)
    {
        $deliveryLogService = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $testTakerIdentifier = $deliveryExecution->getUserIdentifier();
        $events = array_reverse($deliveryLogService->get($deliveryExecution->getIdentifier()));

        $lastTestTakersEvent = null;
        foreach ($events as $event) {
            if ($event[DeliveryLog::CREATED_BY] === $testTakerIdentifier) {
                $lastTestTakersEvent = $event;
                break;
            }
        }

        return $lastTestTakersEvent;
    }

}

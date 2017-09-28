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
use oat\taoQtiTest\models\cat\CatService;
use oat\taoQtiTest\models\runner\config\QtiRunnerConfig;
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

            if (
                ($executionState === DeliveryExecutionState::STATE_AUTHORIZED || $executionState === DeliveryExecutionState::STATE_AWAITING)
                && $deliveryExecutionStateService->isCancelable($deliveryExecution)
            ) {
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

        $testConfig = $this->getServiceManager()->get(QtiRunnerConfig::SERVICE_ID);
        $reviewConfig = $testConfig->getConfigValue('review');
        $displaySubsectionTitle = isset($reviewConfig['displaySubsectionTitle']) ? (bool) $reviewConfig['displaySubsectionTitle'] : true;

        if ($session !== null) {
            if ($session->isRunning()) {
                $route = $session->getRoute();
                $currentItem = $route->current();

                $catService = $this->getServiceManager()->get(CatService::SERVICE_ID);
                $isAdaptive = $catService->isAdaptive($session, $currentItem->getAssessmentItemRef());

                if ($displaySubsectionTitle || $isAdaptive) {
                    $currentSection = $session->getCurrentAssessmentSection();
                    if ($isAdaptive) {
                        $testSessionData = $this->getTestSessionDataById($session->getSessionId());
                        $sectionItems = $catService->getShadowTest($session, $testSessionData['compilation']['private'], $currentItem);
                        $currentItem = $catService->getCurrentCatItemId($session, $testSessionData['compilation']['private'], $currentItem);
                    } else {
                        $sectionItems = $route->getRouteItemsByAssessmentSection($currentSection)->getArrayCopy(true);
                    }
                    $positionInSection = array_search($currentItem, $sectionItems);

                    $result = $this->getProgressText($currentSection->getTitle(), $positionInSection, count($sectionItems));
                } else {
                    // we need only top section and items from there
                    $parts = $this->getMappedItems($session);
                    foreach ($parts as $part) {
                        foreach ($part['sections'] as $section) {
                            foreach ($section['items'] as $key => $item) {
                                if ($currentItem->getAssessmentItemRef()->getIdentifier() == $key) {
                                    $result = $this->getProgressText($section['label'], $item['positionInSection'], count($section['items']));
                                    break 3;
                                 }
                             }
                         }
                     }
                }
            } else {
                $result = __('finished');
            }
        }
        return $result;
    }

    /**
     * @param string $sectionTitle
     * @param int $positionInSection
     * @param int $sectionCount
     * @return string
     */
    private function getProgressText($sectionTitle, $positionInSection, $sectionCount)
    {
        return __('%1$s - Item %2$s/%3$s', $sectionTitle, $positionInSection + 1, $sectionCount);
    }

    /**
     * Load all items as there should be viewed
     * @param $session
     * @return array
     */
    private function getMappedItems($session)
    {
        $parts = [];
        $route = $session->getRoute();
        $routeItems = $route->getAllRouteItems();
        $offset = $route->getRouteItemPosition($routeItems[0]);
        $offsetPart = 0;
        $offsetSection = 0;
        $lastPart = null;
        $lastSection = null;
        foreach ($routeItems as $routeItem) {
            $sections = $routeItem->getAssessmentSections()->getArrayCopy();
            $section = $sections[0];
            $sectionId = $section->getIdentifier();
            $testPart = $routeItem->getTestPart();
            $partId = $testPart->getIdentifier();
            $itemRef = $routeItem->getAssessmentItemRef();
            $itemId = $itemRef->getIdentifier();

            if ($lastPart != $partId) {
                $offsetPart = 0;
                $lastPart = $partId;
            }
            if ($lastSection != $sectionId) {
                $offsetSection = 0;
                $lastSection = $sectionId;
            }

            if (!isset($parts[$partId])) {
                $parts[$partId] = [
                    'label' => $partId,
                    'sections' => []
                ];
            }
            if (!isset($parts[$partId]['sections'][$sectionId])) {
                $parts[$partId]['sections'][$sectionId] = [
                    'label' => $section->getTitle(),
                    'items' => []
                ];
            }

            $parts[$partId]['sections'][$sectionId]['items'][$itemId] = [
                'positionInSection' => $offsetSection,
                'sectionLabel' => $section->getTitle(),
                'partLabel' => $partId
            ];

            $offset ++;
            $offsetSection ++;
            $offsetPart ++;
        }

        return $parts;
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

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

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\oatbox\event\EventManager;
use oat\taoProctoring\model\event\DeliveryExecutionTerminated;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use oat\taoClientDiagnostic\model\browserDetector\WebBrowserService;
use oat\taoClientDiagnostic\model\browserDetector\OSService;


/**
 * Class DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryExecutionStateService extends ConfigurableService implements \oat\taoProctoring\model\DeliveryExecutionStateService
{
    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * Computes the state of the delivery and returns one of the extended state code
     *
     * @param DeliveryExecution $deliveryExecution
     * @return null|string
     * @throws \common_Exception
     */
    public function getState(DeliveryExecution $deliveryExecution)
    {
        return $deliveryExecution->getState()->getUri();
    }

    /**
     * Sets a delivery execution in the awaiting state
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function waitExecution(DeliveryExecution $deliveryExecution)
    {
        $executionState = $this->getState($deliveryExecution);

        if (ProctoredDeliveryExecution::STATE_TERMINATED != $executionState && ProctoredDeliveryExecution::STATE_FINISHED != $executionState) {
            $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_AWAITING);
            return true;
        }
        return false;
    }

    /**
     * Sets a delivery execution in the in progress state
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function resumeExecution(DeliveryExecution $deliveryExecution)
    {
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        $logData = [
            'web_browser_name' => WebBrowserService::singleton()->getClientName(),
            'web_browser_version' => WebBrowserService::singleton()->getClientVersion(),
            'os_name' => OSService::singleton()->getClientName(),
            'os_version' => OSService::singleton()->getClientVersion(),
            'timestamp' => microtime(true),
        ];

        if ($session) {
            $session->resume();
            $this->getTestSessionService()->persist($session);
            $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_RESUME', $logData);
        } else {
            $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_RUN', $logData);
        }
        $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_ACTIVE);

        $result = true;

        return $result;
    }

    /**
     * Authorises a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @param string $testCenter test center uri
     * @return bool
     */
    public function authoriseExecution(DeliveryExecution $deliveryExecution, $reason = null, $testCenter = null)
    {
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (ProctoredDeliveryExecution::STATE_AWAITING === $executionState) {
            $logData = [
                'proctorUri' => \common_session_SessionManager::getSession()->getUser()->getIdentifier(),
                'timestamp' => microtime(true),
            ];
            if (!empty($reason) && is_array($reason)) {
                $logData = array_merge($logData, $reason);
            }
            if ($testCenter !== null) {
                $logData['test_center'] = $testCenter;
            }
            $logData['itemId'] = $this->getCurrentItemId($deliveryExecution);
            $logData['context'] = $this->getProgress($deliveryExecution);
            $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_AUTHORISE', $logData);
            $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_AUTHORIZED);
            $result = true;
        }

        return $result;
    }

    /**
     * Terminates a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function terminateExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (ProctoredDeliveryExecution::STATE_TERMINATED !== $executionState && ProctoredDeliveryExecution::STATE_FINISHED !== $executionState) {
            $proctor = \common_session_SessionManager::getSession()->getUser();
            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->trigger(new DeliveryExecutionTerminated($deliveryExecution, $proctor, $reason));

            $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_TERMINATED);

            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            if ($session) {
                $data = [
                    'reason' => $reason,
                    'timestamp' => microtime(true),
                    'itemId' => $this->getCurrentItemId($deliveryExecution),
                    'context' => $this->getProgress($deliveryExecution)
                ];
                $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_TERMINATE', $data);
                if ($session->isRunning()) {
                    $session->endTestSession();
                }
                $this->getTestSessionService()->persist($session);
            }
            $result = true;
        }

        return $result;
    }

    /**
     * Pauses a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function pauseExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (ProctoredDeliveryExecution::STATE_TERMINATED !== $executionState && ProctoredDeliveryExecution::STATE_FINISHED !== $executionState) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            $data = [
                'reason' => $reason,
                'timestamp' => microtime(true),
            ];
            if ($session) {
                $data['itemId'] = $this->getCurrentItemId($deliveryExecution);
                $data['context'] = $this->getProgress($deliveryExecution);
                $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_PAUSE', $data);
                $session->suspend();
                $this->getTestSessionService()->persist($session);
            } else {
                $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_PAUSE', $data);
                $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_PAUSED);
            }
            $result = true;
        }

        return $result;
    }

    /**
     * Report irregularity to a delivery execution
     *
     * @todo remove this method to separate service
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function reportExecution(DeliveryExecution $deliveryExecution, $reason)
    {
        $deliveryLog = $this->getDeliveryLogService();
        $data = [
            'reason' => $reason,
            'timestamp' => microtime(true),
            'itemId' => $this->getCurrentItemId($deliveryExecution),
            'context' => $this->getProgress($deliveryExecution)
        ];
        return $deliveryLog->log($deliveryExecution->getIdentifier(), 'TEST_IRREGULARITY', $data);
    }

    /**
     * @return \oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService
     */
    private function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * Gets test session service
     *
     * @return TestSessionService
     */
    private function getTestSessionService()
    {
        if ($this->testSessionService === null) {
            $this->testSessionService = ServiceManager::getServiceManager()->get(TestSessionService::SERVICE_ID);
        }
        return $this->testSessionService;
    }

    /**
     * Get identifier of current item.
     * @param DeliveryExecution $deliveryExecution
     * @return null|string
     */
    protected function getCurrentItemId(DeliveryExecution $deliveryExecution)
    {
        $result = null;
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        if ($session) {
            $item = $session->getCurrentAssessmentItemRef();
            if ($item) {
                $result = $item->getIdentifier();
            }
        }
        return $result;
    }

    /**
     * Pause delivery execution if test session was paused.
     * @param TestExecutionPausedEvent $event
     */
    public static function catchSessionPause(TestExecutionPausedEvent $event)
    {
        $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($event->getTestExecutionId());
        $deliveryExecution->setState(ProctoredDeliveryExecution::STATE_PAUSED);
    }

    protected function getProgress(DeliveryExecution $deliveryExecution)
    {
        $result = null;

        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);

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
}

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
use oat\taoQtiTest\models\TestSessionMetaData;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\TestSessionService;

/**
 * Class DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryExecutionStateService extends ConfigurableService implements \oat\taoProctoring\model\DeliveryExecutionStateService
{
    private $executionService;

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /**
     * temporary variable until proper servicemanager integration
     * @var ExtendedStateService
     */
    private $extendedStateService;

    /**
     * Computes the state of the delivery and returns one of the extended state code
     *
     * @param DeliveryExecution $deliveryExecution
     * @return null|string
     * @throws \common_Exception
     */
    public function getState(DeliveryExecution $deliveryExecution)
    {
        $executionStatus = $deliveryExecution->getState()->getUri();
        $proctoringState = $this->getProctoringState($deliveryExecution->getIdentifier());
        $status = $proctoringState['status'];

        if (DeliveryExecution::STATE_FINISHIED == $executionStatus) {
            if (self::STATE_TERMINATED != $status) {
                $status = self::STATE_COMPLETED;
            }
        } else if (!$status) {
            if (DeliveryExecution::STATE_ACTIVE == $executionStatus) {
                $status = self::STATE_INIT;
            } else if (DeliveryExecution::STATE_PAUSED == $executionStatus) {
                $status = self::STATE_PAUSED;
            } else {
                throw new \common_Exception('Unknown state for delivery execution ' . $deliveryExecution->getIdentifier());
            }
        }

        return $status;
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
        $result = false;

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_AWAITING);

            $result = true;
        }

        return $result;
    }

    /**
     * Sets a delivery execution in the inprogress state
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function resumeExecution(DeliveryExecution $deliveryExecution)
    {
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_AUTHORIZED == $executionState) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            if ($session) {
                $session->resume();
                $this->getTestSessionService()->persist($session);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_INPROGRESS);

            $result = true;
        }

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

        if (self::STATE_AWAITING == $executionState) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            if ($session) {
                $this->getTestSessionService()->setTestVariable($session, 'TEST_AUTHORISE', $reason);
                $this->getTestSessionService()->persist($session);
            }

            if ($testCenter !== null) {
                $stateService = $this->getExtendedStateService();
                $proctoringState = $stateService->getValue($deliveryExecution, 'proctoring');
                $proctoringState['test_center'] = $testCenter;
                $stateService->setValue($deliveryExecution, 'proctoring', $proctoringState);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_AUTHORIZED, $reason);

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

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            if ($session) {
                $testSessionMetaData = new TestSessionMetaData($session);
                $testSessionMetaData->save(array(
                    'TEST' => array(
                        'TEST_EXIT_CODE' => TestSessionMetaData::TEST_CODE_TERMINATED,
                        $this->nameTestVariable($session, 'TEST_TERMINATE') => $this->encodeTestVariable($reason)
                    ),
                    'SECTION' => array('SECTION_EXIT_CODE' => TestSessionMetaData::SECTION_CODE_FORCE_QUIT),
                ));

                $session->endTestSession();
                $this->getTestSessionService()->persist($session);
            }

            $deliveryExecution->setState(DeliveryExecution::STATE_FINISHIED);
            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_TERMINATED, $reason);

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

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            if ($session) {
                $this->getTestSessionService()->setTestVariable($session, 'TEST_PAUSE', $reason);
                $session->suspend();
                $this->getTestSessionService()->persist($session);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_PAUSED, $reason);

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
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        //@todo find a way to report it even if the session does not exist
        if ($session) {
            $this->getTestSessionService()->setTestVariable($session, 'TEST_IRREGULARITY', $reason);
            return true;
        }
        return false;
    }

    /**
     * Sets a proctoring state on a delivery execution. Use the test state storage.
     *
     * @todo remove this method to separate service
     * @param string|DeliveryExecution $executionId
     * @param string $state
     * @param array $reason
     */
    public function setProctoringState($executionId, $state, $reason = null)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $stateService = $this->getExtendedStateService();
        $proctoringState = $stateService->getValue($deliveryExecution, 'proctoring');

        $currentUser = \tao_models_classes_UserService::singleton()->getCurrentUser();

        $proctoringState['status'] = $state;
        $proctoringState['reason'] = $reason;
        if ($currentUser !== null && $state === self::STATE_AUTHORIZED) {
            $proctoringState['authorized_by'] = $currentUser->getUri();
        }
        $stateService->setValue($deliveryExecution, 'proctoring', $proctoringState);
    }

    /**
     * Gets a proctoring state from a delivery execution. Use the test state storage.
     *
     * @todo remove this method to separate service
     * @param string|DeliveryExecution $executionId
     * @return array
     */
    public function getProctoringState($executionId)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $stateService = $this->getExtendedStateService();
        $proctoringState = $stateService->getValue($deliveryExecution, 'proctoring');

        if (!isset($proctoringState['status'])) {
            $proctoringState['status'] = null;
        }

        if (!isset($proctoringState['reason'])) {
            $proctoringState['reason'] = null;
        }

        return $proctoringState;
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
     * Gets delivery execution service
     *
     * @return \taoDelivery_models_classes_execution_ServiceProxy
     */
    private function getExecutionService()
    {
        if ($this->executionService === null) {
            $this->executionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();
        }
        return $this->executionService;
    }

    /**
     * temporary helper until proper servicemanager integration
     * @return ExtendedStateService
     */
    private function getExtendedStateService()
    {
        if (!isset($this->extendedStateService)) {
            $this->extendedStateService = new ExtendedStateService();
        }
        return $this->extendedStateService;
    }
}
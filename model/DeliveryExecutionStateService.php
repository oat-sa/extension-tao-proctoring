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

namespace oat\taoProctoring\model;

use oat\taoProctoring\model\implementation\ExtendedStateService;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoDelivery\model\AssignmentService;
use qtism\runtime\storage\binary\BinaryAssessmentTestSeeker;
use qtism\runtime\tests\AssessmentTestSession;
use qtism\runtime\storage\common\AbstractStorage;
use oat\taoQtiTest\models\TestSessionMetaData;

/**
 * Class DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryExecutionStateService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/DeliveryExecutionState';

    const STATE_INIT = 'INIT';
    const STATE_AWAITING = 'AWAITING';
    const STATE_AUTHORIZED = 'AUTHORIZED';
    const STATE_INPROGRESS = 'INPROGRESS';
    const STATE_PAUSED = 'PAUSED';
    const STATE_COMPLETED = 'COMPLETED';
    const STATE_TERMINATED = 'TERMINATED';

    /**
     * Ordered list of allowed states
     * @var array
     */
    private static $allowedStates = array(
        self::STATE_INIT,
        self::STATE_AWAITING,
        self::STATE_AUTHORIZED,
        self::STATE_INPROGRESS,
        self::STATE_PAUSED,
        self::STATE_COMPLETED,
        self::STATE_TERMINATED
    );

    private $executionService;

    /**
     * QtiSm AssessmentTestSession Storage Service
     * @var AbstractStorage
     */
    private $storage;

    /**
     * temporary variable until proper servicemanager integration
     * @var ExtendedStateService
     */
    private $extendedStateService;

    /**
     * Gets the list of allowed states
     * @return array
     */
    public function getAllowedStates()
    {
        return self::$allowedStates;
    }

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
     * @param string $executionId
     * @return bool
     */
    public function waitExecution($executionId)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
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
     * @param string $executionId
     * @return bool
     */
    public function resumeExecution($executionId)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_AUTHORIZED == $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->resumeSession($session);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_INPROGRESS);

            $result = true;
        }

        return $result;
    }

    /**
     * Authorises a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function authoriseExecution($executionId, $reason = null)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_AWAITING == $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->setTestVariable($session, 'TEST_AUTHORISE', $reason);
                $this->getStorage()->persist($session);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_AUTHORIZED, $reason);

            $result = true;
        }

        return $result;
    }

    /**
     * Terminates a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function terminateExecution($executionId, $reason = null)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $testSessionMetaData = new TestSessionMetaData($session);
                $testSessionMetaData->save(array(
                    'TEST' => array(
                        'TEST_EXIT_CODE' => TestSessionMetaData::TEST_CODE_TERMINATED,
                        $this->nameTestVariable($session, 'TEST_TERMINATE') => $this->encodeTestVariable($reason)
                    ),
                    'SECTION' => array('SECTION_EXIT_CODE' => TestSessionMetaData::SECTION_CODE_FORCE_QUIT),
                ));

                $this->finishSession($session);
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
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function pauseExecution($executionId, $reason = null)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->setTestVariable($session, 'TEST_PAUSE', $reason);
                $this->suspendSession($session);
            }

            $this->setProctoringState($deliveryExecution->getIdentifier(), self::STATE_PAUSED, $reason);

            $result = true;
        }

        return $result;
    }

    /**
     * Report irregularity to a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function reportExecution($executionId, $reason)
    {
        $deliveryExecution = $this->getExecutionService()->getDeliveryExecution($executionId);
        $session = $this->getTestSession($deliveryExecution);
        //@todo find a way to report it even if the session does not exist
        if ($session) {
            $this->setTestVariable($session, 'TEST_IRREGULARITY', $reason);
            return true;
        }
        return false;
    }

    /**
     * Sets a proctoring state on a delivery execution. Use the test state storage.
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
     * Gets the test session for a particular deliveryExecution
     *
     * @param DeliveryExecution $deliveryExecution
     * @return \qtism\runtime\tests\AssessmentTestSession
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function getTestSession(DeliveryExecution $deliveryExecution)
    {
        $resultServer = \taoResultServer_models_classes_ResultServerStateFull::singleton();

        $compiledDelivery = $deliveryExecution->getDelivery();
        $inputParameters = $this->getRuntimeInputParameters($deliveryExecution);

        $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
        $testResource = new \core_kernel_classes_Resource($inputParameters['QtiTestDefinition']);

        $sessionManager = new \taoQtiTest_helpers_SessionManager($resultServer, $testResource);

        $qtiStorage = new \taoQtiTest_helpers_TestSessionStorage(
            $sessionManager,
            new BinaryAssessmentTestSeeker($testDefinition), $deliveryExecution->getUserIdentifier()
        );
        $this->setStorage($qtiStorage);

        $sessionId = $deliveryExecution->getIdentifier();

        if ($qtiStorage->exists($sessionId)) {
            $session = $qtiStorage->retrieve($testDefinition, $sessionId);

            $resultServerUri = $compiledDelivery->getOnePropertyValue(new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
            $resultServerObject = new \taoResultServer_models_classes_ResultServer($resultServerUri, array());
            $resultServer->setValue('resultServerUri', $resultServerUri->getUri());
            $resultServer->setValue('resultServerObject', array($resultServerUri->getUri() => $resultServerObject));
            $resultServer->setValue('resultServer_deliveryResultIdentifier', $deliveryExecution->getIdentifier());
        } else {
            $session = null;
        }

        return $session;
    }

    /**
     * Finishes the session of a delivery execution
     *
     * @param AssessmentTestSession $session
     * @throws \qtism\runtime\tests\AssessmentTestSessionException
     */
    public function finishSession(AssessmentTestSession $session)
    {
        if ($session) {
            $session->endTestSession();
            $this->getStorage()->persist($session);
        }
    }

    /**
     * Suspends the session of a delivery execution
     *
     * @param AssessmentTestSession $session
     */
    public function suspendSession(AssessmentTestSession $session)
    {
        if ($session) {
            $session->suspend();
            $this->getStorage()->persist($session);
        }
    }

    /**
     * Resumes the session of a delivery execution
     *
     * @param AssessmentTestSession $session
     */
    public function resumeSession(AssessmentTestSession $session)
    {
        if ($session) {
            $session->resume();
            $this->getStorage()->persist($session);
        }
    }

    /**
     *
     * @param DeliveryExecution $deliveryExecution
     * @return array
     * Exapmple:
     * <pre>
     * array(
     *   'QtiTestCompilation' => 'http://sample/first.rdf#i14369768868163155-|http://sample/first.rdf#i1436976886612156+',
     *   'QtiTestDefinition' => 'http://sample/first.rdf#i14369752345581135'
     * )
     * </pre>
     */
    public function getRuntimeInputParameters(DeliveryExecution $deliveryExecution)
    {
        $compiledDelivery = $deliveryExecution->getDelivery();
        $runtime = $this->getServiceManager()->get(AssignmentService::CONFIG_ID)->getRuntime($compiledDelivery->getUri());
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters;
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

    /**
     * Sets a test variable with name automatic suffix
     * @param AssessmentTestSession $session
     * @param string $name
     * @param mixe $value
     */
    private function setTestVariable(AssessmentTestSession $session, $name, $value)
    {
        $testSessionMetaData = new TestSessionMetaData($session);
        $testSessionMetaData->save(array(
            'TEST' => array(
                $this->nameTestVariable($session, $name) => $this->encodeTestVariable($value)
            )
        ));
    }

    /**
     * Build a variable name based on the current position inside the test
     * @param AssessmentTestSession $session
     * @param string $name
     * @return string
     */
    private function nameTestVariable(AssessmentTestSession $session, $name)
    {
        $varName = array($name);
        if ($session) {
            $varName[] = $session->getCurrentAssessmentItemRef();
            $varName[] = $session->getCurrentAssessmentItemRefOccurence();
            $varName[] = time();
        }
        return implode('.', $varName);
    }

    /**
     * Encodes a test variable
     * @param mixed $value
     * @return string
     */
    private function encodeTestVariable($value)
    {
        return json_encode(array(
            'timestamp' => microtime(),
            'details' => $value
        ));
    }

    /**
     * Get the QtiSm AssessmentTestSession Storage Service.
     *
     * @return AbstractStorage An AssessmentTestSession Storage Service.
     */
    private function getStorage() {
        return $this->storage;
    }

    /**
     * Set the QtiSm AssessmentTestSession Storage Service.
     *
     * @param AbstractStorage $storage An AssessmentTestSession Storage Service.
     */
    private function setStorage(AbstractStorage $storage) {
        $this->storage = $storage;
    }

}
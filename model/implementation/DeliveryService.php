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
namespace oat\taoProctoring\model\implementation;

use oat\oatbox\user\User;
use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\ProctorAssignment;
use core_kernel_users_GenerisUser;
use oat\taoGroups\models\GroupsService;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoFrontOffice\model\interfaces\DeliveryExecution as DeliveryExecutionInt;
use qtism\runtime\storage\binary\BinaryAssessmentTestSeeker;
use oat\taoQtiTest\models\TestSessionMetaData;
use qtism\runtime\storage\common\AbstractStorage;
use qtism\runtime\tests\AssessmentTestSession;
use tao_helpers_Date as DateHelper;
use oat\taoProctoring\model\TestCenterService;

/**
 * Sample Delivery Service for proctoring
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryService extends ConfigurableService
    implements ProctorAssignment
{
    const CONFIG_ID = 'taoProctoring/delivery';
    
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

    const STATE_INIT = 'INIT';
    const STATE_AWAITING = 'AWAITING';
    const STATE_AUTHORIZED = 'AUTHORIZED';
    const STATE_INPROGRESS = 'INPROGRESS';
    const STATE_PAUSED = 'PAUSED';
    const STATE_COMPLETED = 'COMPLETED';
    const STATE_TERMINATED = 'TERMINATED';

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administers';

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


    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @return array
     */
    public function getProctorableDeliveries(User $proctor)
    {
        $testCenterService = TestCenterService::singleton();
        $testCenters = $testCenterService->getTestCentersByProctor($proctor);

        $deliveries = [];
        $eligibilityService = EligibilityService::singleton();
        foreach ($testCenters as $testCenter) {
            $deliveries = array_merge($deliveries, $eligibilityService->getEligibleDeliveries($testCenter));
        }

        return $deliveries;
    }

    /**
     * Gets all deliveries available for a test center
     * @param string|core_kernel_classes_Resource $testCenterId
     * @return \taoDelivery_models_classes_DeliveryRdf[]
     * @deprecated
     */
    public function getTestCenterDeliveries($testCenterId)
    {
        \common_Logger::w('Use of deprecated method: DeliveryService::getTestCenterDeliveries()');
        $testCenter = new \core_kernel_classes_Resource($testCenterId);
        return EligibilityService::singleton()->getEligibleDeliveries($testCenter);
    }

    /**
     * Gets the executions of a delivery
     *
     * @param $deliveryId
     * @param array $options
     * @return DeliveryExecution[]
     */
    public function getDeliveryExecutions($deliveryId, $options = array())
    {
        $resource = new \core_kernel_classes_Resource($deliveryId);
        return \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getExecutionsByDelivery($resource);
    }

    /**
     * Gets a delivery execution from its identifier
     *
     * @param string $executionId
     * @return DeliveryExecution
     */
    public function getDeliveryExecution($executionId)
    {
        if ($executionId instanceof DeliveryExecution) {
            return $executionId;
        }

        $executionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();
        return $executionService->getDeliveryExecution($executionId);
    }

    /**
     * @param DeliveryExecution $a
     * @param DeliveryExecution $b
     * @return int
     */
    public function cmpDeliveryExecution($a, $b) {
        return DateHelper::getTimeStamp($b->getStartTime()) - DateHelper::getTimeStamp($a->getStartTime());
    }

    /**
     * Gets the active or paused executions of a delivery
     *
     * @param $deliveryId
     * @param array $options
     * @return DeliveryExecution[]
     */
    public function getCurrentDeliveryExecutions($deliveryId, $options = array())
    {
        $deliveryExecutions = $this->getDeliveryExecutions($deliveryId);
        usort($deliveryExecutions, array($this, 'cmpDeliveryExecution'));
        return $deliveryExecutions;
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
        $proctoringState = $this->getProctoringState($deliveryExecution);
        $status = $proctoringState['status'];

        if (DeliveryExecutionInt::STATE_FINISHIED == $executionStatus) {
            if (self::STATE_TERMINATED != $status) {
                $status = self::STATE_COMPLETED;
            }
        } else if (!$status) {
            if (DeliveryExecutionInt::STATE_ACTIVE == $executionStatus) {
                $status = self::STATE_INIT;
            } else if (DeliveryExecutionInt::STATE_PAUSED == $executionStatus) {
                $status = self::STATE_PAUSED;
            } else {
                throw new \common_Exception('Unknown state for delivery execution ' . $deliveryExecution->getIdentifier());
            }
        }
        
        return $status;
    }

    /**
     * Sets a proctoring state on a delivery execution. Use the test state storage.
     * @param string|DeliveryExecution $executionId
     * @param string $state
     * @param array $reason
     */
    public function setProctoringState($executionId, $state, $reason = null)
    {
        $deliveryExecution = $this->getDeliveryExecution($executionId);
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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
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
     * Gets the list of allowed states
     * @return array
     */
    public function getAllowedStates()
    {
        return self::$allowedStates;
    }

    /**
     *
     * @param string $deliveryId
     * @return \taoDelivery_models_classes_DeliveryRdf
     */
    public function getDelivery($deliveryId)
    {
        return new \taoDelivery_models_classes_DeliveryRdf($deliveryId);
    }

    /**
     * Gets the properties of a particular delivery
     *
     * @param string|core_kernel_classes_Resource $delivery
     * @return mixed
     */
    public function getDeliveryProperties($delivery)
    {
        if (is_string($delivery)) {
            $delivery = new \core_kernel_classes_Resource($delivery);
        }

        $deliveryProps = $delivery->getPropertiesValues(array(
            new \core_kernel_classes_Property(TAO_DELIVERY_MAXEXEC_PROP),
            new \core_kernel_classes_Property(TAO_DELIVERY_START_PROP),
            new \core_kernel_classes_Property(TAO_DELIVERY_END_PROP),
        ));

        $propMaxExec = current($deliveryProps[TAO_DELIVERY_MAXEXEC_PROP]);
        $propStartExec = current($deliveryProps[TAO_DELIVERY_START_PROP]);
        $propEndExec = current($deliveryProps[TAO_DELIVERY_END_PROP]);

        $settings[TAO_DELIVERY_MAXEXEC_PROP] = (!(is_object($propMaxExec)) or ($propMaxExec=="")) ? 0 : $propMaxExec->literal;
        $settings[TAO_DELIVERY_START_PROP] = (!(is_object($propStartExec)) or ($propStartExec=="")) ? null : $propStartExec->literal;
        $settings[TAO_DELIVERY_END_PROP] = (!(is_object($propEndExec)) or ($propEndExec=="")) ? null : $propEndExec->literal;

        return $settings;
    }

    /**
     * Gets the test takers assigned to a delivery
     *
     * @param $deliveryId
     * @param array $options
     * @return User[]
     */
    public function getDeliveryTestTakers($deliveryId, $options = array())
    {
        $delivery = new \core_kernel_classes_Resource($deliveryId);
        $userIds = \taoDelivery_models_classes_AssignmentService::singleton()->getAssignedUsers($delivery);
        $users = array();
        foreach ($userIds as $id) {
            // assume Tao Users
            $users[] = new core_kernel_users_GenerisUser(new \core_kernel_classes_Resource($id));
        }
        return $users;
    }

    /**
     * Gets the test takers available for a delivery
     *
     * @param User $proctor
     * @param string $deliveryId
     * @param array $options
     * @return User[]
    */
    public function getAvailableTestTakers(User $proctor, $deliveryId, $options = array())
    {
        $testCenterService = TestCenterService::singleton();

        // test takers already assigned are excluded
        $excludeIds = array();
        foreach ($this->getDeliveryTestTakers($deliveryId) as $user) {
            $excludeIds[$user->getIdentifier()] = true;
        }

        // determine testcenters managed per proctor with delivery available
        $availableIn = array();
        foreach ($testCenterService->getTestCentersByDelivery($deliveryId) as $testCenter) {
            $availableIn[$testCenter->getUri()] = true;
        }

        $testCenters = array();
        foreach ($testCenterService->getTestCentersByProctor($proctor) as $testCenter) {
            if (array_key_exists($testCenter->getUri(), $availableIn)) {
                $testCenters[] = $testCenter;
            }
        }

        // get testtakers from those centers that are not excluded
        $users = array();
        foreach ($testCenters as $testCenter) {
            foreach ($testCenterService->getTestTakers($testCenter->getUri()) as $userResource) {
                $uri = $userResource->getUri();
                if (!array_key_exists($uri, $excludeIds) && !array_key_exists($uri, $users)) {
                    $users[$uri] = new \core_kernel_users_GenerisUser($userResource);
                }
            }
        }
        return array_values($users);
    }

    /**
     * Assign a test taker to a delivery
     *
     * Assumes:
     * Deliveries are assigned via groups
     * Users are in the ontology
     *
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorAssignment::assignTestTaker()
     */
    public function assignTestTaker($testTakerId, $deliveryId)
    {
        $deliveryGroup = new \core_kernel_classes_Resource($this->findGroup($deliveryId));
        return GroupsService::singleton()->addUser($testTakerId, $deliveryGroup);
    }

    /**
     * Unassign (remove) a test taker to a delivery
     *
     * Assumes:
     * Deliveries are assigned via groups
     * Users are in the ontology
     *
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorAssignment::unassignTestTaker()
     */
    public function unassignTestTaker($testTakerId, $deliveryId)
    {
        $deliveryGroup = new \core_kernel_classes_Resource($this->findGroup($deliveryId));
        return GroupsService::singleton()->removeUser($testTakerId, $deliveryGroup);
    }

    /**
     * Sets a delivery execution in the awaiting state
     *
     * @param string $executionId
     * @return bool
     */
    public function waitExecution($executionId)
    {
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $this->setProctoringState($deliveryExecution, self::STATE_AWAITING);

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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_AUTHORIZED == $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->resumeSession($session);
            }

            $this->setProctoringState($deliveryExecution, self::STATE_INPROGRESS);

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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_AWAITING == $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->setTestVariable($session, 'TEST_AUTHORISE', $reason);
                $this->getStorage()->persist($session);
            }

            $this->setProctoringState($deliveryExecution, self::STATE_AUTHORIZED, $reason);

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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
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

            $deliveryExecution->setState(DeliveryExecutionInt::STATE_FINISHIED);
            $this->setProctoringState($deliveryExecution, self::STATE_TERMINATED, $reason);

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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $executionState = $this->getState($deliveryExecution);
        $result = false;

        if (self::STATE_TERMINATED != $executionState && self::STATE_COMPLETED != $executionState) {
            $session = $this->getTestSession($deliveryExecution);
            if ($session) {
                $this->setTestVariable($session, 'TEST_PAUSE', $reason);
                $this->suspendSession($session);
            }

            $this->setProctoringState($deliveryExecution, self::STATE_PAUSED, $reason);

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
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $session = $this->getTestSession($deliveryExecution);
        //@todo find a way to report it even if the session does not exist
        if ($session) {
            $this->setTestVariable($session, 'TEST_IRREGULARITY', $reason);
            return true;
        }
        return false;
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
     * Returns a group assinged to the delivery
     *
     * @param string $deliveryId
     * @return string
     */
    private function findGroup($deliveryId)
    {
        $groups = GroupsService::singleton()->getRootClass()->searchInstances(array(
            PROPERTY_GROUP_DELVIERY => $deliveryId
        ), array(
            'recursive' => true, 'like' => false
        ));
        if (empty($groups)) {
            \common_Logger::w('No system group exists for delivery '.$deliveryId.'. creating one');
            $delivery = new \core_kernel_classes_Resource($deliveryId);
            $newGroup = GroupsService::singleton()->getRootClass()->createInstance('test takers for delivery '.$delivery->getLabel());
            $newGroup->setPropertyValue(new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY), $deliveryId);
            return $newGroup;
        }
        return reset($groups);
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
        $runtime = \taoDelivery_models_classes_DeliveryAssemblyService::singleton()->getRuntime($compiledDelivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters;
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
}

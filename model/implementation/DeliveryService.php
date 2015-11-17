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
use oat\taoProctoring\model\ProctorAssignment;
use core_kernel_users_GenerisUser;
use oat\taoGroups\models\GroupsService;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoFrontOffice\model\interfaces\DeliveryExecution as DeliveryExecutionInt;
use qtism\runtime\storage\binary\BinaryAssessmentTestSeeker;
use oat\taoQtiTest\models\TestSessionMetaData;
use qtism\runtime\storage\common\AbstractStorage;

/**
 * Sample Delivery Service for proctoring
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryService extends ConfigurableService
    implements ProctorAssignment
{
    /**
     * QtiSm AssessmentTestSession Storage Service
     * @var AbstractStorage
     */
    private $storage;

    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @param array $options
     * @return array
     */
    public function getProctorableDeliveries(User $proctor, $options = array())
    {
        $service = \taoDelivery_models_classes_DeliveryAssemblyService::singleton();
        $allDeliveries = array();
        foreach ($service->getRootClass()->getInstances(true) as $deliveryResource) {
            $allDeliveries[] = new \taoDelivery_models_classes_DeliveryRdf($deliveryResource);
        }
        return $allDeliveries;
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
     * @return \taoDelivery_models_classes_execution_DeliveryExecution
     */
    public function getDeliveryExecution($executionId)
    {
        $executionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();
        return $executionService->getDeliveryExecution($executionId);
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
        $executions = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            $status = $deliveryExecution->getState()->getUri();
            if (DeliveryExecutionInt::STATE_ACTIVE == $status || DeliveryExecutionInt::STATE_PAUSED == $status) {
                $executions[] = $deliveryExecution;
            }
        }

        return $executions;
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
     * @param core_kernel_classes_Resource $delivery
     * @return mixed
     */
    public function getDeliveryProperties($delivery)
    {
        if (is_object($delivery) && !($delivery instanceof \core_kernel_classes_Resource)) {
            $delivery = $delivery->getUri();
        }

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
        $class = new  \core_kernel_classes_Class(TAO_SUBJECT_CLASS);
        
        $excludeIds = array();
        foreach ($this->getDeliveryTestTakers($deliveryId) as $user) {
            $excludeIds[] = $user->getIdentifier();
        }
        
        $users = array();
        foreach ($class->getInstances(true) as $userResource) {
            // assume Tao Users
            if (!in_array($userResource->getUri(), $excludeIds)) {
                $users[] = new core_kernel_users_GenerisUser($userResource);
            }
        }
        return $users;
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
     * Authorises a delivery execution
     *
     * @param string $executionId
     * @return bool
     */
    public function authoriseExecution($executionId)
    {
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $session = $this->getTestSession($deliveryExecution);

        $session->resume();

        $this->getStorage()->persist($session);
        return true;
    }

    /**
     * Terminates a delivery execution
     *
     * @param string $executionId
     * @return bool
     */
    public function terminateExecution($executionId)
    {
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $session = $this->getTestSession($deliveryExecution);

        $testSessionMetaData = new TestSessionMetaData($session);
        $testSessionMetaData->save(array(
            'TEST' => array('TEST_EXIT_CODE' => TestSessionMetaData::TEST_CODE_TERMINATED),
            'SECTION' => array('SECTION_EXIT_CODE' => TestSessionMetaData::SECTION_CODE_FORCE_QUIT),
        ));

        $session->endTestSession();
        $deliveryExecution->setState(DeliveryExecutionInt::STATE_FINISHIED);

        $this->getStorage()->persist($session);
        return true;
    }

    /**
     * Pauses a delivery execution
     *
     * @param string $executionId
     * @return bool
     */
    public function pauseExecution($executionId)
    {
        $deliveryExecution = $this->getDeliveryExecution($executionId);
        $session = $this->getTestSession($deliveryExecution);

        $session->suspend();

        $this->getStorage()->persist($session);
        return true;
    }
    
    /**
     * Returns a group assinged to the delivery
     * 
     * @param unknown $deliveryId
     * @return string
     * @throws \common_Exception
     */
    private function findGroup($deliveryId)
    {
        $groups = GroupsService::singleton()->getRootClass()->searchInstances(array(
            PROPERTY_GROUP_DELVIERY => $deliveryId
        ), array(
            'recursive' => true, 'like' => false
        ));
        if (empty($groups)) {
            throw new \common_Exception('No system group exists for delivery '.$deliveryId);
        }
        return reset($groups);
    }

    /**
     * Gets the test session for a particular deliveryExecution
     *
     * @param DeliveryExecution $deliveryExecution
     * @return \qtism\runtime\tests\AssessmentTestSession
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    private function getTestSession(DeliveryExecution $deliveryExecution)
    {
        $resultServer = \taoResultServer_models_classes_ResultServerStateFull::singleton();

        $compiledDelivery = $deliveryExecution->getDelivery();
        $runtime = \taoDelivery_models_classes_DeliveryAssemblyService::singleton()->getRuntime($compiledDelivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
        $testResource = new \core_kernel_classes_Resource($inputParameters['QtiTestDefinition']);

        $sessionManager = new \taoQtiTest_helpers_SessionManager($resultServer, $testResource);

        $qtiStorage = new \taoQtiTest_helpers_TestSessionStorage(
            $sessionManager,
            new BinaryAssessmentTestSeeker($testDefinition), $deliveryExecution->getUserIdentifier()
        );
        $this->setStorage($qtiStorage);

        $session = $qtiStorage->retrieve($testDefinition, $deliveryExecution->getIdentifier());
        $resultServerUri = $compiledDelivery->getOnePropertyValue(new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
        $resultServerObject = new \taoResultServer_models_classes_ResultServer($resultServerUri, array());

        $resultServer->setValue('resultServerUri', $resultServerUri->getUri());
        $resultServer->setValue('resultServerObject', array($resultServerUri->getUri() => $resultServerObject));
        $resultServer->setValue('resultServer_deliveryResultIdentifier', $deliveryExecution->getIdentifier());

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
}

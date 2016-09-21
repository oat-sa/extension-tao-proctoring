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

use core_kernel_classes_Property as Property;
use core_kernel_classes_Resource as Resource;
use oat\oatbox\user\User;
use oat\oatbox\service\ConfigurableService;
use oat\tao\helpers\UserHelper;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\ProctorAssignment;
use core_kernel_users_GenerisUser;
use oat\taoGroups\models\GroupsService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\TestCenterService;
use oat\taoTestTaker\models\TestTakerService;
use tao_helpers_Date as DateHelper;
use oat\taoProctoring\helpers\DeliveryHelper;

/**
 * Sample Delivery Service for proctoring
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryService extends ConfigurableService
    implements ProctorAssignment
{
    const CONFIG_ID = 'taoProctoring/delivery';

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administers';

    const PROPERTY_GROUP_TEST_CENTERS = 'http://www.tao.lu/Ontologies/TAOGroup.rdf#TestCenters';

    const GROUP_CLASS_NAME = 'groups for proctoring';

    private $groupClass = null;

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
     * @param string|Resource $testCenterId
     * @return array
     * @deprecated
     */
    public function getTestCenterDeliveries($testCenterId)
    {
        \common_Logger::w('Use of deprecated method: DeliveryService::getTestCenterDeliveries()');
        $testCenter = new Resource($testCenterId);
        return EligibilityService::singleton()->getEligibleDeliveries($testCenter);
    }

    /**
     * Gets the executions of a delivery
     *
     * @param $deliveryId
     * @param array $options
     * @return DeliveryExecution[]
     * @deprecated
     */
    public function getDeliveryExecutions($deliveryId, $options = array())
    {
        $resource = new Resource($deliveryId);
        return \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getExecutionsByDelivery($resource);
    }

    /**
     * Compare two deliveryExecution by their start timestamp.
     * CAUTION: getStartTime() is not cached at this moment, so it request the DB on each call
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
     * @param $testCenterId
     * @param array $options
     * @return DeliveryExecution[]
     * @deprecated
     */
    public function getCurrentDeliveryExecutions($deliveryId, $testCenterId, $options = array())
    {
        $deliveryExecutions = $this->getDeliveryExecutions($deliveryId);
        $returnedDeliveryExecutions = array();

        $group = $this->findGroup($deliveryId, $testCenterId);
        $users = GroupsService::singleton()->getUsers($group);
        $userIds = array();
        foreach ($users as $user) {
            $userIds[] = $user->getUri();
        }

        foreach ($deliveryExecutions as $deliveryExecution) {
            if (in_array($deliveryExecution->getUserIdentifier(), $userIds)) {
                $returnedDeliveryExecutions[] = $deliveryExecution;
            }
        }

        return $returnedDeliveryExecutions;
    }

    /**
     *
     * @param string $deliveryId
     * @return \core_kernel_classes_Resource
     */
    public function getDelivery($deliveryId)
    {
        return new \core_kernel_classes_Resource($deliveryId);
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
            $delivery = new Resource($delivery);
        }

        $deliveryProps = $delivery->getPropertiesValues(array(
            new Property(TAO_DELIVERY_MAXEXEC_PROP),
            new Property(TAO_DELIVERY_START_PROP),
            new Property(TAO_DELIVERY_END_PROP),
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
     * @param string $testCenterId
     * @return User[]
     */
    public function getDeliveryTestTakers($deliveryId, $testCenterId, $options = array())
    {
        $groups = $this->getGroupClass()->searchInstances(array(
            PROPERTY_GROUP_DELVIERY => $deliveryId,
            self::PROPERTY_GROUP_TEST_CENTERS => $testCenterId
        ), array('recursive' => true, 'like' => false));

        $userIds = array();
        foreach ($groups as $group) {
            foreach (GroupsService::singleton()->getUsers($group) as $user) {
                $userIds[] = $user->getUri();
            }
        }

        $userIds = array_unique($userIds);
        $users = array();
        foreach ($userIds as $id) {
            // assume Tao Users
            $users[] = new core_kernel_users_GenerisUser(new Resource($id));
        }

        usort($users, function ($a, $b) {
            return strcasecmp(
                UserHelper::getUserLastName($a),
                UserHelper::getUserLastName($b)
            );
        });


        return $users;
    }

    /**
     * Gets the test takers available for a delivery
     *
     * @param User $proctor
     * @param string $deliveryId
     * @param string $testCenterId
     * @param array $options
     * @return User[]
    */
    public function getAvailableTestTakers(User $proctor, $deliveryId, $testCenterId, $options = array())
    {
        $testCenterService = TestCenterService::singleton();

        // test takers already assigned are excluded
        $excludeIds = array();
        foreach ($this->getDeliveryTestTakers($deliveryId, $testCenterId) as $user) {
            $excludeIds[$user->getIdentifier()] = true;
        }

        // determine testcenters managed per proctor with delivery available
        $availableIn = array();
        foreach ($testCenterService->getTestCentersByDelivery($deliveryId) as $testCenter) {
            $availableIn[$testCenter->getUri()] = true;
        }

        // get testtakers from those centers that are not excluded
        $users = array();
        foreach ($testCenterService->getTestCentersByProctor($proctor) as $testCenter) {
            $testCenterUri = $testCenter->getUri();
            if (array_key_exists($testCenterUri, $availableIn)) {
                foreach ($testCenterService->getTestTakers($testCenterUri) as $userResource) {
                    $uri = $userResource->getUri();
                    if (!array_key_exists($uri, $excludeIds) && !array_key_exists($uri, $users)) {
                        $users[$uri] = new \core_kernel_users_GenerisUser($userResource);
                    }
                }
            }
        }

        return array_values($users);
    }

    /**
     * Assign a test taker to a delivery in the contexte of a test center
     *
     * Assumes:
     * Deliveries are assigned via groups
     * Users are in the ontology
     *
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorAssignment::assignTestTaker()
     */
    public function assignTestTaker($testTakerId, $deliveryId, $testCenterId)
    {
        $deliveryGroup = new Resource($this->findGroup($deliveryId, $testCenterId));
        return GroupsService::singleton()->addUser($testTakerId, $deliveryGroup);
    }

    /**
     * Unassign (remove) a test taker to a delivery in the context of a test center
     *
     * Assumes:
     * Deliveries are assigned via groups
     * Users are in the ontology
     *
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorAssignment::unassignTestTaker()
     */
    public function unassignTestTaker($testTakerId, $deliveryId, $testCenterId)
    {
        $deliveryGroup = new Resource($this->findGroup($deliveryId, $testCenterId));
        return GroupsService::singleton()->removeUser($testTakerId, $deliveryGroup);
    }

    /**
     * Remove the group that link test takers to the delivery in a test center
     * @param string $deliveryId
     * @param string $testCenterId
     * @return bool whatever the group has been removed or not
     */
    public function removeAvailability($deliveryId, $testCenterId)
    {
        // remove group for this delivery and this test center
        $deliveryGroup = new Resource($this->findGroup($deliveryId, $testCenterId));
        return $deliveryGroup->delete(true);
    }

    /**
     * Returns a group assigned to the delivery
     *
     * @param string $deliveryId
     * @param string $testCenterId
     * @return Resource
     */
    private function findGroup($deliveryId, $testCenterId)
    {
        $groups = $this->getGroupClass()->searchInstances(
            array(
                PROPERTY_GROUP_DELVIERY => $deliveryId,
                self::PROPERTY_GROUP_TEST_CENTERS => $testCenterId
            ),
            array(
            'recursive' => true, 'like' => false
        ));
        if (empty($groups)) {
            \common_Logger::w('No system group exists for delivery '.$deliveryId.' and test center '.$testCenterId.'. creating one');
            $delivery = new Resource($deliveryId);
            $testCenter = new Resource($testCenterId);
            $instanceName = 'test takers for delivery '.$delivery->getLabel().' and test center '.$testCenter->getLabel();


            $newGroup = $this->getGroupClass()->createInstanceWithProperties(
                array(
                    RDFS_LABEL => $instanceName,
                    PROPERTY_GROUP_DELVIERY => $deliveryId,
                    self::PROPERTY_GROUP_TEST_CENTERS => $testCenterId
                )
            );
            return $newGroup;
        }
        return reset($groups);
    }

    /**
     * Get the group class where the group are stored
     * @return \core_kernel_classes_Class|null
     */
    private function getGroupClass()
    {
        if(is_null($this->groupClass)){
            $subClasses = GroupsService::singleton()->getRootClass()->getSubClasses();
            foreach($subClasses as $subClass){
                if($subClass->getLabel() === self::GROUP_CLASS_NAME){
                    $this->groupClass = $subClass;
                    continue;
                }
            }
            if(is_null($this->groupClass)){
                $this->groupClass = GroupsService::singleton()->getRootClass()->createSubClass(self::GROUP_CLASS_NAME);
            }

        }
        return $this->groupClass;

    }

    /**
     * @deprecated please use DeliveryHelper
     */
    public function getHasBeenPaused($deliveryExecution){
        return DeliveryHelper::getHasBeenPaused($deliveryExecution);
    }

    /**
     * @deprecated please use DeliveryHelper
     */
    public function setHasBeenPaused($deliveryExecution){
        return DeliveryHelper::setHasBeenPaused($deliveryExecution);
    }
}

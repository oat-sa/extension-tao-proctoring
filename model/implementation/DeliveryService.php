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
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\AssignmentService;
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
    
    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administers';

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
        $userIds = $this->getServiceManager()->get(AssignmentService::CONFIG_ID)->getAssignedUsers($deliveryId);
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
}

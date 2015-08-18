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
/**
 * Sample Delivery Service for proctoring
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryService extends ConfigurableService
    implements ProctorAssignment
{

    public function getProctorableDeliveries(User $proctor)
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
     * @return taoDelivery_models_classes_execution_DeliveryExecution[]
     */
    public function getDeliveryExecutions($deliveryId)
    {
        $resource = new \core_kernel_classes_Resource($deliveryId);
        return \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getExecutionsByDelivery($resource);
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
     * Returns a group assinged to the delivery
     * 
     * @param unknown $deliveryId
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
            throw new \common_Exception('No system group exists for delivery '.$deliveryId);
        }
        return reset($groups);
    }
    
}

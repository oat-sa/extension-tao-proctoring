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
namespace oat\taoProctoring\model;

use oat\oatbox\user\User;
use oat\taoTestTaker\models\TestTakerService;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use core_kernel_classes_Property;
use tao_models_classes_ClassService;
use taoDelivery_models_classes_DeliveryRdf;

/**
 * TestCenter Service for proctoring
 * 
 */
class TestCenterService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#TestCenter';

    const PROPERTY_MEMBERS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#member';

    const PROPERTY_PROCTORS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#proctor';

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administers';
    
    /**
     * return the test center top level class
     *
     * @access public
     * @return core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return new core_kernel_classes_Class(self::CLASS_URI);
    }

    /**
     * Get test centers administered by a proctor
     *
     * @param User $user
     * @return core_kernel_classes_Resource[]
     * @throws \common_exception_Error
     */
    public function getTestCentersByProctor(User $user) {
        $testCenters = array();
        foreach ($user->getPropertyValues(self::PROPERTY_PROCTORS_URI) as $id) {
            $testCenters[] = new core_kernel_classes_Resource($id);
        }
        return $testCenters;
    }

    /**
     * Get test centers a test-taker is assigned to
     *
     * @access public
     * @param  User $user
     * @return array resources of testcenter
     */
    public function getTestCentersByTestTaker(User $user)
    {
        $testCenters = $user->getPropertyValues(self::PROPERTY_MEMBERS_URI);
        array_walk($testCenters, function (&$testCenter) {
            $testCenter = new core_kernel_classes_Resource($testCenter);
        });

        return $testCenters;
    }

    /**
     * Get test centers a delivery can be taken from
     *
     * @access public
     * @param  string $deliveryUri
     * @return array resources of testcenter
     */
    public function getTestCentersByDelivery($deliveryUri)
    {
        return $this->getRootClass()->searchInstances(array(
            self::PROPERTY_DELIVERY_URI => $deliveryUri
        ), array(
            'recursive' => true, 'like' => false
        ));
    }

    /**
     * Gets test center
     *
     * @param string $testCenterUri
     * @return core_kernel_classes_Resource
     */
    public function getTestCenter($testCenterUri) {
        return new core_kernel_classes_Resource($testCenterUri);
    }

    /**
     *
     * @param string $testCenterUri
     * @return \taoDelivery_models_classes_DeliveryRdf[]
     */
    public function getDeliveries($testCenterUri)
    {
        $testCenter = new core_kernel_classes_Resource($testCenterUri);
        $deliveryProp = new core_kernel_classes_Property(self::PROPERTY_DELIVERY_URI);

        $deliveries = array();
        foreach ($testCenter->getPropertyValues($deliveryProp) as $deliveryUri) {
            $delivery = new taoDelivery_models_classes_DeliveryRdf($deliveryUri);
            if ($delivery->exists()) {
                $deliveries[] = $delivery;
            }
        }
        return $deliveries;
    }

    /**
     * gets the users of a test center
     *
     * @param string $testCenterUri
     * @return array resources of users
     */
    public function getTestTakers($testCenterUri)
    {
        $userClass = TestTakerService::singleton()->getRootClass();
        $users = $userClass->searchInstances(array(
            self::PROPERTY_MEMBERS_URI => $testCenterUri
        ), array(
            'recursive' => true,
            'like' => false
        ));

        return $users;
    }

    /**
     * Add a test-taker to a test center
     *
     * @param string $userUri
     * @param core_kernel_classes_Resource $testCenter
     * @return boolean
     */
    public function addTestTaker($userUri, core_kernel_classes_Resource $testCenter)
    {
        $user = new core_kernel_classes_Resource($userUri);
        return $user->setPropertyValue(new core_kernel_classes_Property(self::PROPERTY_MEMBERS_URI), $testCenter);
    }

    /**
     * Remove a test-taker from a test center
     *
     * @param string $userUri
     * @param core_kernel_classes_Resource $testCenter
     * @return boolean
     */
    public function removeTestTaker($userUri, core_kernel_classes_Resource $testCenter)
    {
        $user = new core_kernel_classes_Resource($userUri);
        return $user->removePropertyValue(new core_kernel_classes_Property(self::PROPERTY_MEMBERS_URI), $testCenter);
    }
}

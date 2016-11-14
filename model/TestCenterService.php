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

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\oatbox\user\User;
use oat\taoTestTaker\models\TestTakerService;
use tao_models_classes_ClassService;

/**
 * TestCenter Service for proctoring
 * 
 */
class TestCenterService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#TestCenter';

    const PROPERTY_MEMBERS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#member';//deprecated

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administers';//deprecated

    const PROPERTY_CHILDREN_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#children';

    const PROPERTY_ADMINISTRATOR_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administrator';

    const PROPERTY_AUTHORIZED_PROCTOR_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#authorizedProctor';
    
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
     * (non-PHPdoc)
     * @see tao_models_classes_ClassService::deleteResource()
     */
    public function deleteResource(core_kernel_classes_Resource $resource)
    {
        $success = parent::deleteResource($resource);
        if ($success) {
            $userClass = new \core_kernel_classes_Class(CLASS_TAO_USER);
            // cleanup proctors
            $users = $userClass->searchInstances(
                array(
                    ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI => $resource->getUri()
                ),
                array(
                    'recursive' => true,
                    'like' => false
                )
            );
            foreach ($users as $user) {
                $user->removePropertyValue(
                    new core_kernel_classes_Property(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI),
                    $resource
                );
            }
            // cleanup admins
            $users = $userClass->searchInstances(
                array(
                    ProctorManagementService::PROPERTY_ADMINISTRATOR_URI => $resource->getUri()
                ),
                array(
                    'recursive' => true,
                    'like' => false
                )
            );
            foreach ($users as $user) {
                $user->removePropertyValue(
                    new core_kernel_classes_Property(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI),
                    $resource
                );
            }
            // @todo cleanup eligibilities
        }

        return $success;
    }

    /**
     * Merge several list of resources URIs into one array
     * @param array $uris
     * @param array ...
     * @return array
     */
    protected function mergeActualResources($uris)
    {
        $resources = array();
        foreach (func_get_args() as $uris) {
            if (!is_array($uris)) {
                $uris = [$uris];
            }
            foreach ($uris as $uri) {
                $resource = new core_kernel_classes_Resource($uri);
                if ($resource->exists()) {
                    $resources[$uri] = $resource;
                }
            }
        }

        return $resources;
    }

    /**
     * Get test centers administered by a proctor
     *
     * @param User $user
     * @return core_kernel_classes_Resource[]
     * @throws \common_exception_Error
     */
    public function getTestCentersByProctor(User $user)
    {
        $testCenters = array();
        $testCenters = array_merge($testCenters, $user->getPropertyValues(self::PROPERTY_AUTHORIZED_PROCTOR_URI));
        foreach($user->getPropertyValues(self::PROPERTY_AUTHORIZED_PROCTOR_URI) as $testCenter){
            $testCenters = array_merge($testCenters, $this->getSubTestCenters($testCenter));
        }
        $testCenters = array_merge($testCenters, $user->getPropertyValues(self::PROPERTY_ADMINISTRATOR_URI));
        foreach($user->getPropertyValues(self::PROPERTY_ADMINISTRATOR_URI) as $testCenter){
            $testCenters = array_merge($testCenters, $this->getSubTestCenters($testCenter));
        }
        $testCenters = $this->mergeActualResources($testCenters);

        return $testCenters;
    }

    public function getSubTestCenters($testCenter)
    {
        if(! $testCenter instanceof core_kernel_classes_Resource){
            $testCenter = new core_kernel_classes_Resource($testCenter);
        }
        $childrenProperty = new core_kernel_classes_Property(self::PROPERTY_CHILDREN_URI);
        return $testCenter->getPropertyValues($childrenProperty);

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
        return $this->mergeActualResources(
            $user->getPropertyValues(self::PROPERTY_MEMBERS_URI)
        );
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
        return $this->getRootClass()->searchInstances(
            array(
                self::PROPERTY_DELIVERY_URI => $deliveryUri
            ),
            array(
                'recursive' => true,
                'like' => false
            )
        );
    }

    /**
     * Gets test center
     *
     * @param string $testCenterUri
     * @return core_kernel_classes_Resource
     */
    public function getTestCenter($testCenterUri)
    {
        return new core_kernel_classes_Resource($testCenterUri);
    }

    /**
     *
     * @param string $testCenterUri
     * @return \core_kernel_classes_Resource[]
     */
    public function getDeliveries($testCenterUri)
    {
        $testCenter = new core_kernel_classes_Resource($testCenterUri);
        $deliveryProp = new core_kernel_classes_Property(self::PROPERTY_DELIVERY_URI);

        $deliveries = array();
        foreach ($testCenter->getPropertyValues($deliveryProp) as $deliveryUri) {
            $delivery = new \core_kernel_classes_Resource($deliveryUri);
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
        $users = $userClass->searchInstances(
            array(
                self::PROPERTY_MEMBERS_URI => $testCenterUri
            ),
            array(
                'recursive' => true,
                'like' => false
            )
        );

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

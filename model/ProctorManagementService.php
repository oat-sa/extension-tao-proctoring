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
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use core_kernel_classes_Property;
use tao_models_classes_ClassService;

/**
 * Proctor management Service
 * 
 */
class ProctorManagementService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#User';

    const PROPERTY_PROCTORS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#proctor';

    const PROPERTY_ADMINISTRATOR_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#administrator';

    const PROPERTY_AUTHORIZED_PROCTOR_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#authorizedProctor';

    const PROPERTY_ASSIGNED_PROCTOR_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#assignedProctor';

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
     * Allow test center administrator to authorize multiple proctors to test centers
     * @param $proctorsUri
     * @param $testCenters
     * @return bool
     */
    public function authorizeProctors($proctorsUri, $testCenters){
        $return = true;
        $propertiesValues = array(self::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters);
        foreach($proctorsUri as $proctorUri){
            $proctor = new core_kernel_classes_Resource($proctorUri);
            $return &= $proctor->setPropertiesValues($propertiesValues);
        }

        return (bool) $return;
    }

    /**
     * Allow test center administrator to remove authorization to multiple proctors
     * @param $proctorsUri
     * @param $testCenters
     * @return bool
     */
    public function unauthorizeProctors($proctorsUri, $testCenters){
        $return = true;
        foreach($proctorsUri as $proctorUri){
            $proctor = new core_kernel_classes_Resource($proctorUri);
            foreach($testCenters as $testCenter){
                $return &= $proctor->removePropertyValue(new core_kernel_classes_Property(self::PROPERTY_AUTHORIZED_PROCTOR_URI), $testCenter);

            }
        }

        return (bool) $return;
    }

    /**
     * Allow test center administrator to assign multiple proctors to test centers
     * @param $proctorsUri
     * @param $testCenterAdminUri
     * @return bool
     */
    public function assignProctors($proctorsUri, $testCenterAdminUri){
        $return = true;
        $testCenterAdmin = new core_kernel_classes_Resource($testCenterAdminUri);
        $testCenters = $testCenterAdmin->getPropertyValues(new core_kernel_classes_Property(self::PROPERTY_ADMINISTRATOR_URI));
        if(!empty($testCenters)){
            $propertiesValues = array(self::PROPERTY_ASSIGNED_PROCTOR_URI => $testCenters);
            foreach($proctorsUri as $proctorUri){
                $proctor = new core_kernel_classes_Resource($proctorUri);
                $return &= $proctor->setPropertiesValues($propertiesValues);
            }
        }else{
            throw new \common_Exception('proctors cannot be assigned to a test center admin that has no authorized test center');
        }
        return (bool) $return;
    }

    /**
     * Return all proctor assigned to test centers managed by an admin
     * @param string $testCenterAdminUri
     * @return User[]
     */
    public function getAssignedProctors($testCenterAdminUri){
        $testCenterAdmin = new core_kernel_classes_Resource($testCenterAdminUri);
        $testCenters = $testCenterAdmin->getPropertyValues(new core_kernel_classes_Property(self::PROPERTY_ADMINISTRATOR_URI));
        $proctorClass = $this->getRootClass();
        $users = array();
        foreach($testCenters as $testCenterUri){
            $assignedProctors = $proctorClass->searchInstances(array(
                self::PROPERTY_ASSIGNED_PROCTOR_URI => $testCenterUri
            ), array(
                'recursive' => true,
                'like' => false
            ));
            $users = array_merge($users , $assignedProctors);
        }

        return $users;
    }

    /**
     * Return authorization for a list of test centers
     * @param $testCenters
     * @return array(proctorUri => array(testcenters))
     */
    public function getProctorsAuthorization($testCenters){
        $proctorClass = $this->getRootClass();
        $users = array();

        $authorizedProctors = $proctorClass->searchInstances(array(
            self::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters
        ), array(
            'recursive' => true,
            'like' => false,
            'chaining' => 'or'
        ));

        /** @var core_kernel_classes_Resource $proctor */
        foreach($authorizedProctors as $proctor){
            $testCenters = $proctor->getPropertyValues(new core_kernel_classes_Property(self::PROPERTY_AUTHORIZED_PROCTOR_URI));
            $users[$proctor->getUri()] = $testCenters;
        }

        return $users;


    }
}

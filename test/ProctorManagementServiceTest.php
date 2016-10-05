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

namespace oat\taoProctoring\test;


use core_kernel_classes_Class;
use core_kernel_classes_Property;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\model\ProctorManagementService;


/**
 * Test the Test center service
 *
 * @package taoTestCenter
 */
class ProctorManagementServiceTest extends TaoPhpUnitTestRunner
{

    /**
     * @var ProctorManagementService
     */
    protected $proctorManagementService = null;


    /**
     * tests initialization
     */
    public function setUp()
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoTestTaker');
        TaoPhpUnitTestRunner::initTest();
        $this->proctorManagementService = ProctorManagementService::singleton();

        //clean test resources
        $proctor1 = new \core_kernel_classes_Resource('http://myTest.case#proctor1');
        $proctor2 = new \core_kernel_classes_Resource('http://myTest.case#proctor2');
        $proctor3 = new \core_kernel_classes_Resource('http://myTest.case#proctor3');

        $proctor1->delete(true);
        $proctor2->delete(true);
        $proctor3->delete(true);
    }

    public function testProctorRoot()
    {
        $proctorClass = $this->proctorManagementService->getRootClass();
        $this->assertInstanceOf('core_kernel_classes_Class', $proctorClass);
        $this->assertEquals(ProctorManagementService::CLASS_URI, $proctorClass->getUri());
    }


    public function testAuthorizeProctors(){

        $testCenters = array('TestCenter1', 'TestCenter2');
        $proctorClass = $this->proctorManagementService->getRootClass();
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctor3 = $proctorClass->createInstance('proctor3', '', 'http://myTest.case#proctor3');
        $proctors = array($proctor1->getUri(), $proctor2->getUri(), $proctor3->getUri());

        $return = $this->proctorManagementService->authorizeProctors($proctors, $testCenters);
        //there is no way to be sure of the return order
        $this->assertEquals(
            $testCenters,
            $proctor1->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertEquals(
            $testCenters,
            $proctor2->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertEquals($testCenters,
            $proctor3->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertTrue($return);

        $return = $this->proctorManagementService->authorizeProctors(array($proctor1->getUri()), $testCenters);
        $this->assertEquals(
            $testCenters,
            $proctor1->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertTrue($return);

        $proctor1->delete(true);
        $proctor2->delete(true);
        $proctor3->delete(true);
    }

    public function testUnauthorizeProctors(){
        $testCenters = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2',
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4'
        );
        $propertiesValues = array(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters);

        $proctorClass = $this->proctorManagementService->getRootClass();
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor1->setPropertiesValues($propertiesValues);
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctor2->setPropertiesValues($propertiesValues);
        $proctor3 = $proctorClass->createInstance('proctor3', '', 'http://myTest.case#proctor3');
        $proctor3->setPropertiesValues($propertiesValues);
        $proctors = array($proctor1->getUri(), $proctor2->getUri(), $proctor3->getUri());

        $testCentersToExclude = array('http://myTest.case#TestCenter4', 'http://myTest.case#TestCenter2');
        $testCentersRemaining = array('http://myTest.case#TestCenter1', 'http://myTest.case#TestCenter3');

        $return = $this->proctorManagementService->unauthorizeProctors($proctors, $testCentersToExclude);
        $this->assertEquals(
            $testCentersRemaining,
            $proctor1->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertEquals(
            $testCentersRemaining,
            $proctor2->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertEquals(
            $testCentersRemaining,
            $proctor3->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI)),
            'Proctor is not authorized in the good test centers',
            0.0,
            10,
            true);
        $this->assertTrue($return);
        $proctor1->delete(true);
        $proctor2->delete(true);
        $proctor3->delete(true);
    }

    public function testAssignProctors(){
        $testCenters = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2',
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4'
        );
        $testCenterAdmin = $this->proctorManagementService->getRootClass()->createInstance('test Center', '', 'http://myTest.case#testCenterAdmin');
        $testCenterAdmin->setPropertiesValues(array(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI => $testCenters));

        $proctorClass = $this->proctorManagementService->getRootClass();
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctors = array($proctor1, $proctor2);
        $return = $this->proctorManagementService->assignProctors($proctors, $testCenterAdmin->getUri());
        $this->assertEquals(
            $testCenters,
            $proctor1->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI)),
            'Proctor is not assigned in the good test centers',
            0.0,
            10,
            true);
        $this->assertEquals(
            $testCenters,
            $proctor2->getPropertyValues(new core_kernel_classes_Property(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI)),
            'Proctor is not assigned in the good test centers',
            0.0,
            10,
            true);

        $this->assertTrue($return);
        $proctor1->delete(true);
        $proctor2->delete(true);
        $testCenterAdmin->delete(true);
    }

    public function testGetAssignedProctors(){

        $testCenters = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2',
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4'
        );
        $testCenterAdmin = $this->proctorManagementService->getRootClass()->createInstance('test Center', '', 'http://myTest.case#testCenterAdmin');
        $testCenterAdmin->setPropertiesValues(array(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI => $testCenters));

        $proctorClass = $this->proctorManagementService->getRootClass();
        $propertiesValues = array(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI => $testCenters);
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor1->setPropertiesValues($propertiesValues);
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctor2->setPropertiesValues($propertiesValues);

        $assignedProctor = $this->proctorManagementService->getAssignedProctors($testCenterAdmin->getUri());
        $this->assertCount(2, $assignedProctor);
        $this->assertArrayHasKey($proctor1->getUri(), $assignedProctor);
        $this->assertArrayHasKey($proctor2->getUri(), $assignedProctor);
        $proctor1->delete(true);
        $proctor2->delete(true);
        $testCenterAdmin->delete(true);
    }

    public function testGetAssignedProctorsWithTestCenters(){

        $testCenters = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2',
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4'
        );
        $testCenterAdmin = $this->proctorManagementService->getRootClass()->createInstance('test Center', '', 'http://myTest.case#testCenterAdmin');
        $testCenterAdmin->setPropertiesValues(array(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI => $testCenters));

        $proctorClass = $this->proctorManagementService->getRootClass();
        $propertiesValues = array(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI => $testCenters);
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor1->setPropertiesValues($propertiesValues);
        $propertiesValues = array(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI => array('http://myTest.case#TestCenter2'));
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctor2->setPropertiesValues($propertiesValues);

        $assignedProctor = $this->proctorManagementService->getAssignedProctors($testCenterAdmin->getUri(), $testCenters);
        $this->assertCount(2, $assignedProctor);
        $this->assertArrayHasKey($proctor1->getUri(), $assignedProctor);
        $this->assertArrayHasKey($proctor2->getUri(), $assignedProctor);
        $assignedProctor = $this->proctorManagementService->getAssignedProctors($testCenterAdmin->getUri(), array('http://myTest.case#TestCenter1'));
        $this->assertCount(1, $assignedProctor);
        $this->assertArrayHasKey($proctor1->getUri(), $assignedProctor);
        $proctor1->delete(true);
        $proctor2->delete(true);
        $testCenterAdmin->delete(true);
    }

    public function testGetProctorsAuthorization(){

        $testCenters = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2',
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4',
            'http://myTest.case#TestCenter5'
        );
        $testCenters1 = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter2'
        );

        $testCenters2 = array(
            'http://myTest.case#TestCenter3',
            'http://myTest.case#TestCenter4'
        );

        $testCenters3 = array(
            'http://myTest.case#TestCenter1',
            'http://myTest.case#TestCenter4'
        );

        $proctorClass = $this->proctorManagementService->getRootClass();
        $propertiesValues = array(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters1);
        $proctor1 = $proctorClass->createInstance('proctor1', '', 'http://myTest.case#proctor1');
        $proctor1->setPropertiesValues($propertiesValues);
        $propertiesValues = array(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters2);
        $proctor2 = $proctorClass->createInstance('proctor2', '', 'http://myTest.case#proctor2');
        $proctor2->setPropertiesValues($propertiesValues);
        $propertiesValues = array(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI => $testCenters3);
        $proctor3 = $proctorClass->createInstance('proctor3', '', 'http://myTest.case#proctor3');
        $proctor3->setPropertiesValues($propertiesValues);

        $authorization = $this->proctorManagementService->getProctorsAuthorization($testCenters);
        $this->assertCount(4, $authorization);
        $this->assertArrayHasKey($proctor1->getUri(), $authorization);
        $this->assertArrayHasKey($proctor2->getUri(), $authorization);
        $this->assertArrayHasKey($proctor3->getUri(), $authorization);

        $this->assertEquals(
            $testCenters1,
            $authorization[$proctor1->getUri()],
            'Proctor has not the right authorization',
            0.0,
            10,
            true);

        $this->assertEquals(
            $testCenters2,
            $authorization[$proctor2->getUri()],
            'Proctor has not the right authorization',
            0.0,
            10,
            true);

        $this->assertEquals(
            $testCenters3,
            $authorization[$proctor3->getUri()],
            'Proctor has not the right authorization',
            0.0,
            10,
            true);
        $proctor1->delete(true);
        $proctor2->delete(true);
        $proctor3->delete(true);
    }

}
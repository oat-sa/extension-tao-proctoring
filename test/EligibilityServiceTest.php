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
use core_kernel_classes_Resource;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\model\EligibilityService;


/**
 * Test the Test center service
 *
 * @package taoProctoring
 */
class EligibilityServiceTest extends TaoPhpUnitTestRunner
{

    /**
     * @var EligibilityService
     */
    private $eligilityService = null;

    /**
     * tests initialization
     */
    public function setUp()
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoTestTaker');
        TaoPhpUnitTestRunner::initTest();
        $this->eligilityService = EligibilityService::singleton();
    }

    public function testEligibilityRoot()
    {
        $eligibilityClass = $this->eligilityService->getRootClass();
        $this->assertInstanceOf('core_kernel_classes_Class', $eligibilityClass);
        $this->assertEquals('http://www.tao.lu/Ontologies/TAOProctor.rdf#DeliveryEligibility', $eligibilityClass->getUri());

    }

    /**
     * @expectedException \common_exception_InconsistentData
     * @expectedExceptionMessage Multiple eligibilities for testcenter myTestCenter and delivery myDelivery
     */
    public function testCreateEligibilityMultipleAlreadyExists() {

        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');
        $eligibility = new core_kernel_classes_Resource('Eligibility');
        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootClass'))
            ->getMock();

        $rootClassProphet = $this->prophesize('core_kernel_classes_Class');
        $rootClassProphet->searchInstances(array(
            EligibilityService::PROPERTY_TESTCENTER_URI => $testCenter,
            EligibilityService::PROPERTY_DELIVERY_URI => $delivery
        ), array('recursive' => false, 'like' => false))
            ->willReturn(array($eligibility, $eligibility));

        $eligibilityServiceMock->expects($this->once())
            ->method('getRootClass')
            ->willReturn($rootClassProphet->reveal());

        $eligibilityServiceMock->createEligibility($testCenter, $delivery);

    }

    public function testCreateEligibilityAlreadyExists() {

        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');
        $eligibility = new core_kernel_classes_Resource('Eligibility');
        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootClass'))
            ->getMock();

        $rootClassProphet = $this->prophesize('core_kernel_classes_Class');
        $rootClassProphet->searchInstances(array(
            EligibilityService::PROPERTY_TESTCENTER_URI => $testCenter,
            EligibilityService::PROPERTY_DELIVERY_URI => $delivery
        ), array('recursive' => false, 'like' => false))
            ->willReturn(array($eligibility));

        $eligibilityServiceMock->expects($this->once())
            ->method('getRootClass')
            ->willReturn($rootClassProphet->reveal());

        $returnValue = $eligibilityServiceMock->createEligibility($testCenter, $delivery);

        $this->assertFalse($returnValue);
    }

    public function testCreateEligibility() {

        $eligibility = new core_kernel_classes_Resource('Eligibility');
        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');

        $rootClassProphet = $this->prophesize('core_kernel_classes_Class');
        $rootClassProphet->searchInstances(array(
            EligibilityService::PROPERTY_TESTCENTER_URI => $testCenter,
            EligibilityService::PROPERTY_DELIVERY_URI => $delivery
        ), array('recursive' => false, 'like' => false))
        ->willReturn(array());
        $rootClassProphet->createInstanceWithProperties(array(
                EligibilityService::PROPERTY_TESTCENTER_URI => $testCenter,
                EligibilityService::PROPERTY_DELIVERY_URI => $delivery)
        )->willReturn($eligibility);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootClass'))
            ->getMock();
        $eligibilityServiceMock->expects($this->exactly(2))
            ->method('getRootClass')
            ->willReturn($rootClassProphet->reveal());
        $returnValue = $eligibilityServiceMock->createEligibility($testCenter, $delivery);

        $this->assertTrue($returnValue);
    }

    public function testGetEligibleDeliveries() {
        $testCenter = new core_kernel_classes_Resource('myTestCenter');

        $delivery1 = new core_kernel_classes_Resource('myFirstDelivery');
        $delivery2 = new core_kernel_classes_Resource('mySecondDelivery');
        $delivery3 = new core_kernel_classes_Resource('myThirdDelivery');

        $eligibleProphet1 = $this->prophesize('core_kernel_classes_Resource');
        $eligibleProphet2 = $this->prophesize('core_kernel_classes_Resource');
        $eligibleProphet3 = $this->prophesize('core_kernel_classes_Resource');
        $property = new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileDelivery');
        $eligibleProphet1->getOnePropertyValue($property)->willReturn($delivery1);
        $eligibleProphet2->getOnePropertyValue($property)->willReturn($delivery2);
        $eligibleProphet3->getOnePropertyValue($property)->willReturn($delivery3);
        $eligibles = array($eligibleProphet1->reveal(),$eligibleProphet2->reveal(),$eligibleProphet3->reveal());

        $this->eligilityService->getEligibleDeliveries($testCenter);

        $rootClassProphet = $this->prophesize('\core_kernel_classes_Class');
        $rootClassProphet->searchInstances(array(
            EligibilityService::PROPERTY_TESTCENTER_URI => $testCenter,
        ), array('recursive' => false, 'like' => false))
            ->willReturn($eligibles);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootClass'))
            ->getMock();
        $eligibilityServiceMock->expects($this->once())
            ->method('getRootClass')
            ->willReturn($rootClassProphet->reveal());

        $deliveries = $eligibilityServiceMock->getEligibleDeliveries($testCenter);

        $this->assertCount(3,$deliveries);
        $this->assertEquals($delivery1, $deliveries[0]);
        $this->assertEquals($delivery2, $deliveries[1]);
        $this->assertEquals($delivery3, $deliveries[2]);
    }

    /**
     * @expectedException \oat\taoProctoring\model\IneligibileException
     * @expectedExceptionMessage Delivery myDelivery ineligible to test center myTestCenter
     */
    public function testRemoveFalseEligibility(){
        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getEligibility'))
            ->getMock();

        $eligibilityServiceMock->expects($this->once())
            ->method('getEligibility')
            ->with($testCenter, $delivery)
            ->willReturn(null);
        $eligibilityServiceMock->removeEligibility($testCenter, $delivery);

    }

    public function testRemoveEligibility(){
        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');
        $eligibilityProphet = $this->prophesize('core_kernel_classes_Resource');
        $eligibilityProphet->delete()->willReturn(true);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getEligibility'))
            ->getMock();

        $eligibilityServiceMock->expects($this->once())
            ->method('getEligibility')
            ->with($testCenter, $delivery)
            ->willReturn($eligibilityProphet->reveal());
        $eligibilityServiceMock->removeEligibility($testCenter, $delivery);
    }

    public function testGetEligibleTestTakers(){

        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');

        $testTaker1 = new core_kernel_classes_Resource('TestTaker1');
        $testTaker2 = new core_kernel_classes_Resource('TestTaker2');
        $testTaker3 = new core_kernel_classes_Resource('TestTaker3');
        $testTakers = array($testTaker1, $testTaker2, $testTaker3);

        $eligibilityProphet = $this->prophesize('core_kernel_classes_Resource');
        $eligibilityProphet
            ->getPropertyValues(new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestTaker'))
            ->willReturn($testTakers);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getEligibility'))
            ->getMock();

        $eligibilityServiceMock->expects($this->once())
            ->method('getEligibility')
            ->with($testCenter, $delivery)
            ->willReturn($eligibilityProphet->reveal());

        $eligibles = $eligibilityServiceMock->getEligibleTestTakers($testCenter, $delivery);

        $this->assertCount(3,$eligibles);
        $this->assertEquals('TestTaker1', $eligibles[0]);
        $this->assertEquals('TestTaker2', $eligibles[1]);
        $this->assertEquals('TestTaker3', $eligibles[2]);

    }

    public function testSetEligibleTestTakers(){

        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');

        $testTaker1 = new core_kernel_classes_Resource('TestTaker1');
        $testTaker2 = new core_kernel_classes_Resource('TestTaker2');
        $testTaker3 = new core_kernel_classes_Resource('TestTaker3');
        $testTakers = array($testTaker1, $testTaker2, $testTaker3);

        $eligibilityProphet = $this->prophesize('core_kernel_classes_Resource');
        $eligibilityProphet
            ->editPropertyValues(
                new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestTaker'),
                $testTakers)
            ->willReturn(true);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getEligibility'))
            ->getMock();

        $eligibilityServiceMock->expects($this->once())
            ->method('getEligibility')
            ->with($testCenter, $delivery)
            ->willReturn($eligibilityProphet->reveal());



        $eligibilityServiceMock->setEligibleTestTakers($testCenter, $delivery, $testTakers);
    }

    /**
     * @expectedException \oat\taoProctoring\model\IneligibileException
     * @expectedExceptionMessage Delivery myDelivery ineligible to test center myTestCenter
     */
    public function testWrongSetEligibleTestTakers(){

        $testCenter = new core_kernel_classes_Resource('myTestCenter');
        $delivery = new core_kernel_classes_Resource('myDelivery');

        $testTaker1 = new core_kernel_classes_Resource('TestTaker1');
        $testTakers = array($testTaker1);

        $eligibilityServiceMock = $this->getMockBuilder('oat\taoProctoring\model\EligibilityService')
            ->disableOriginalConstructor()
            ->setMethods(array('getEligibility'))
            ->getMock();

        $eligibilityServiceMock->expects($this->once())
            ->method('getEligibility')
            ->with($testCenter, $delivery)
            ->willReturn(null);



        $eligibilityServiceMock->setEligibleTestTakers($testCenter, $delivery, $testTakers);
    }


}
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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\test\unit\model\authorization;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use Exception;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoProctoring\model\delivery\DeliverySyncService;
use oat\taoDelivery\model\execution\DeliveryExecution;

class TestTakerAuthorizationServiceTest extends TestCase
{
    /**
     * @var Ontology|MockObject
     */
    private $ontologyMock;

    /**
     * @var TestTakerAuthorizationService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();
        $this->ontologyMock = $this->getMock(Ontology::class);
        $this->service = new TestTakerAuthorizationService();

    }
    /**
     * @dataProvider isActiveUnSecureDeliveryDataProvider
     * @param string $propertyValue
     * @param string $state
     * @param bool $expected
     * @throws Exception
     */
    public function testIsActiveUnSecureDelivery($propertyValue, $state, $expected)
    {
        $delivery = $this->getDeliveryMock();
        $property = new core_kernel_classes_Property(
            'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures'
        );
        $this->ontologyMock->expects($this->once())->method('getResource')->with('deliveryUri');

        $this->ontologyMock->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures')
            ->willReturn($property);

        $delivery->expects($this->once())
            ->method('getOnePropertyValue')
            ->with($property)
            ->willReturn($propertyValue);

        $this->service->setServiceLocator($this->getServiceLocatorMock([Ontology::SERVICE_ID => $this->ontologyMock]));

        if ($expected) {
            $this->assertTrue($this->service->isActiveUnSecureDelivery('deliveryUri',$state));
        } else {
            $this->assertFalse($this->service->isActiveUnSecureDelivery('deliveryUri',$state));
        }
    }

    /**
     * @return array
     */
    public function isActiveUnSecureDeliveryDataProvider()
    {
        return [
            'activeAndUnSecure' => [
                null,
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                true
            ],
            'activeAndUnSecure2' => [
                'feature,feature2',
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                true
            ],
            'notActiveAndUnSecure' => [
                'feature,feature2',
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
                false
            ],
            'ActiveAndSecure' => [
                'feature,security',
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                false
            ],
            'notActiveAndSecure' => [
                'feature,security',
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
                false
            ],
            'notActiveAndSecure2' => [
                'security',
                'state',
                false
            ]
        ];
    }

    /**
     * @dataProvider getInvalidStates
     * @param string $state
     * @throws Exception
     */
    public function testVerifyResumeAuthorizationWithInvalidState($state)
    {
        $deliveryExecutionMock = $this->getDeliveryExecutionMock($state);
        $this->service->setServiceLocator($this->getServiceLocatorMock([Ontology::SERVICE_ID => $this->ontologyMock]));

        $this->expectException(UnAuthorizedException::class);
        $this->service->verifyResumeAuthorization($deliveryExecutionMock, $this->getMock(User::class));

    }

    /**
     * @return array
     */
    public function getInvalidStates()
    {
        return [
            ['http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished'],
            ['http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled'],
            ['http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated'],
        ];
    }

    /**
     * @dataProvider isProctoredDataProvider
     * @param core_kernel_classes_Property|null $propertyValue
     * @param bool $proctorByDefault
     * @param bool $expected
     */
    public function testIsProctored($propertyValue, $proctorByDefault, $expected)
    {
        $deliverySyncServiceMock = $this->getMock(DeliverySyncService::class);
        $delivery = $this->getDeliveryMock();

        $property = new core_kernel_classes_Property(
            'http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible'
        );

        $this->ontologyMock->expects($this->once())->method('getResource')->with('deliveryUri');

        $this->ontologyMock->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible')
            ->willReturn($property);

        $delivery->expects($this->once())
            ->method('getOnePropertyValue')
            ->with($property)
            ->willReturn($propertyValue);

        $deliverySyncServiceMock->method('isProctoredByDefault')->willReturn($proctorByDefault);

        $this->service->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontologyMock,
                    DeliverySyncService::SERVICE_ID => $deliverySyncServiceMock
                ]
            )
        );

        if ($expected) {
            $this->assertTrue(
                $this->service->isProctored('deliveryUri', $this->getMock(User::class))
            );
        } else {
            $this->assertFalse(
                $this->service->isProctored('deliveryUri', $this->getMock(User::class))
            );
        }
    }

    /**
     * @return array
     */
    public function isProctoredDataProvider()
    {
        return [
            'byDefault' => [null, true, true],
            'byDefaultNo' => [null, false, false],
            'proctored' => [
                new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'),
                false,
                true
            ],
            'notProctored' => [
                new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyDisabled'),
                true,
                false
            ],
        ];
    }

    /**
     * @dataProvider verifyResumeAuthorizationDataProvider
     * @param string $state
     * @param bool $isSuccess
     * @throws Exception
     */
    public function testVerifyResumeAuthorization($state, $isSuccess)
    {
        $this->ontologyMock->method('getProperty')
            ->willReturnOnConsecutiveCalls(
                new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'),
                new core_kernel_classes_Property(
                    'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures'
                )
            );

        $delivery = $this->getDeliveryMock();
        $delivery->method('getOnePropertyValue')->willReturnOnConsecutiveCalls(
            new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'),
            'feature'
        );

        $deliveryExecutionMock = $this->getDeliveryExecutionMock($state);
        $deliveryExecutionMock->method('getDelivery')->willReturn($delivery);

        $this->service->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    Ontology::SERVICE_ID => $this->ontologyMock,
                    DeliverySyncService::SERVICE_ID => $this->getMock(DeliverySyncService::class)
                ]
            )
        );
        if (!$isSuccess) {
            $this->expectException(UnAuthorizedException::class);
        }
        $this->service->verifyResumeAuthorization($deliveryExecutionMock, $this->getMock(User::class));
    }

    /**
     * @return array
     */
    public function verifyResumeAuthorizationDataProvider()
    {
        return [
            ['state', false],
            ['http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized', true]
        ];
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getDeliveryMock()
    {
        $delivery = $this
            ->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['deliveryUri'])
            ->getMock();
        $delivery->method('getUri')->willReturn('deliveryUri');

        $this->ontologyMock->method('getResource')->willReturn($delivery);

        return $delivery;
    }

    /**
     * @param string $state
     * @return DeliveryExecution|MockObject
     * @throws Exception
     */
    private function getDeliveryExecutionMock($state)
    {
        $deliveryExecutionMock = $this
            ->getMockBuilder(DeliveryExecution::class)
            ->disableOriginalConstructor()
            ->getMock();

        $deliveryExecutionMock->method('getState')->willReturn(new core_kernel_classes_Resource($state));

        return $deliveryExecutionMock;
    }
}

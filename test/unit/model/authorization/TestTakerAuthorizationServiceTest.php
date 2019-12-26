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
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;

class TestTakerAuthorizationServiceTest extends TestCase
{
    /**
     * @dataProvider isActiveUnSecureDeliveryDataProvider
     * @param string $propertyValue
     * @param string $state
     * @param bool $expected
     * @throws Exception
     */
    public function testIsActiveUnSecureDelivery($propertyValue, $state, $expected)
    {
        $ontologyMock = $this->getMock(Ontology::class);

        $delivery = $this
            ->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['deliveryUri'])
            ->getMock();

        $property = new core_kernel_classes_Property(
            'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures'
        );

        $ontologyMock->expects($this->once())
            ->method('getResource')
            ->with('deliveryUri')
            ->willReturn($delivery);

        $ontologyMock->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures')
            ->willReturn($property);

        $delivery->expects($this->once())
            ->method('getOnePropertyValue')
            ->with($property)
            ->willReturn($propertyValue);

        $service = (new TestTakerAuthorizationService());
        $service->setServiceLocator($this->getServiceLocatorMock([Ontology::SERVICE_ID => $ontologyMock]));

        if ($expected) {
            $this->assertTrue($service->isActiveUnSecureDelivery('deliveryUri',$state));
        } else {
            $this->assertFalse($service->isActiveUnSecureDelivery('deliveryUri',$state));
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
        $ontologyMock = $this->getMock(Ontology::class);
        $deliveryExecutionMock = $this->getMock(DeliveryExecutionInterface::class);
        $userMock = $this->getMock(User::class);

        $state = new core_kernel_classes_Resource($state);
        $deliveryExecutionMock->expects($this->once())->method('getState')->willReturn($state);

        $service = (new TestTakerAuthorizationService());
        $service->setServiceLocator($this->getServiceLocatorMock([Ontology::SERVICE_ID => $ontologyMock]));

        $this->expectException(UnAuthorizedException::class);
        $service->verifyResumeAuthorization($deliveryExecutionMock, $userMock);

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
}

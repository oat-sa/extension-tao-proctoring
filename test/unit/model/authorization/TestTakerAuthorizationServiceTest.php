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
use common_Exception;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\taoDelivery\model\DeliveryContainerService;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoProctoring\model\delivery\DeliverySyncService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoTests\models\runner\plugins\TestPlugin;

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

    /**
     * @var DeliveryContainerService|MockObject
     */
    private $deliveryContainerMock;

    /**
     * @var DeliverySyncService|MockObject
     */
    private $deliverySyncServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->deliveryContainerMock = $this->createMock(DeliveryContainerService::class);
        $this->deliverySyncServiceMock = $this->createMock(DeliverySyncService::class);

        $this->service = new TestTakerAuthorizationService();

        $this->service->setServiceLocator($this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontologyMock,
            DeliveryContainerService::SERVICE_ID => $this->deliveryContainerMock,
            DeliverySyncService::SERVICE_ID => $this->deliverySyncServiceMock,
        ]));
    }

    /**
     * @dataProvider isActiveUnSecureDeliveryDataProvider
     * @param string $enabledPlugins
     * @param string $state
     * @param bool $expected
     * @throws Exception
     */
    public function testIsActiveUnSecureDelivery($enabledPlugins, $state, $expected)
    {
        $pluginsMocks = $this->getPluginsMocks($enabledPlugins);
        $this->deliveryContainerMock->method('getPlugins')
            ->willReturn($pluginsMocks);

        $deliveryExecutionMock = $this->getDeliveryExecutionMock($state);

        $this->assertEquals($expected, $this->service->isActiveUnSecureDelivery($deliveryExecutionMock, $state));
    }

    /**
     * @return array
     */
    public function isActiveUnSecureDeliveryDataProvider()
    {
        return [
            'Execution active, not secure mode (empty plugins list)' => [
                'enabledPlugins' => [],
                'state' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                'expected' => true
            ],
            'Execution active, not secure mode (pause plugin disabled)' => [
                'enabledPlugins' => ['PLUGIN2', 'PLUGIN2'],
                'state' =>'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                'expected' => true
            ],
            'Execution not active, not secure mode (pause plugin disabled)' => [
                'enabledPlugins' => ['PLUGIN2', 'PLUGIN2'],
                'state' =>'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
                'expected' => false
            ],
            'Execution active, secure mode (pause plugin enabled)' => [
                'enabledPlugins' => ['PLUGIN2', 'PLUGIN2', 'blurPause'],
                'state' =>'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive',
                'expected' => false
            ],
            'Execution not active, secure mode (pause plugin enabled)' => [
                'enabledPlugins' => ['PLUGIN2', 'PLUGIN2', 'blurPause'],
                'state' =>'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized',
                'expected' => false
            ],
            'Execution not active, secure mode (only pause plugin enabled)' => [
                'propertyValue' => ['blurPause'],
                'state' => 'state',
                'expected' => false
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

        $this->expectException(UnAuthorizedException::class);
        $this->service->verifyResumeAuthorization($deliveryExecutionMock, $this->createMock(User::class));
    }

    /**
     * @return array
     */
    public function getInvalidStates()
    {
        return [
            ['state' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished'],
            ['state' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusCanceled'],
            ['state' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated']
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
        $property = new core_kernel_classes_Property(
            'http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible'
        );
        $delivery = $this->getDeliveryMock();
        $delivery->expects($this->once())
            ->method('getOnePropertyValue')
            ->with($property)
            ->willReturn($propertyValue);

        $this->ontologyMock->expects($this->once())
            ->method('getResource')
            ->with('deliveryUri');
        $this->ontologyMock->expects($this->once())
            ->method('getProperty')
            ->with('http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible')
            ->willReturn($property);

        $this->deliverySyncServiceMock->method('isProctoredByDefault')->willReturn($proctorByDefault);

        $this->assertEquals(
            $expected,
            $this->service->isProctored('deliveryUri', $this->createMock(User::class))
        );
    }

    /**
     * @return array
     */
    public function isProctoredDataProvider()
    {
        return [
            'byDefault' => ['propertyValue' => null, 'proctorByDefault' => true, 'expected' => true],
            'byDefaultNo' => ['propertyValue' => null, 'proctorByDefault' => false, 'expected' => false],
            'proctored' => [
                'propertyValue'
                => new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'),
                'proctorByDefault' => false,
                'expected' => true
            ],
            'notProctored' => [
                'propertyValue' =>
                    new core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyDisabled'),
                'proctorByDefault' => true,
                'expected' => false
            ],
        ];
    }

    public function testVerifyResumeAuthorization()
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

        $deliveryExecutionMock = $this->getDeliveryExecutionMock('state');
        $deliveryExecutionMock->method('getDelivery')->willReturn($delivery);

        $this->expectException(UnAuthorizedException::class);
        $this->service->verifyResumeAuthorization($deliveryExecutionMock, $this->createMock(User::class));
    }

    /**
     * @param array $enabledPlugins
     * @param bool $expectedResult
     * @throws common_Exception
     *
     * @dataProvider dataProviderTestIsSecure
     */
    public function testIsSecure(array $enabledPlugins, $expectedResult)
    {
        $pluginsMock = $this->getPluginsMocks($enabledPlugins);
        $this->deliveryContainerMock->method('getPlugins')
            ->willReturn($pluginsMock);

        $deliveryExecutionMock = $this->getDeliveryExecutionMock('STATE');

        $result = $this->service->isSecure($deliveryExecutionMock);
        $this->assertEquals($expectedResult, $result, 'Result of checking if test is secure must be as expected.');
    }

    /**
     * @return array
     */
    public function dataProviderTestIsSecure()
    {
        return [
            'No enabled plugins' => [
                'enabledPlugins' => [],
                'expectedResult' => false,
            ],
            'Pause plugin not enabled' => [
                'enabledPlugins' => ['DUMMY_PLUGIN_1', 'DUMMY_PLUGIN_2'],
                'expectedResult' => false,
            ],
            'Only pause plugin enabled' => [
                'enabledPlugins' => ['blurPause'],
                'expectedResult' => true,
            ],
            'Multiple plugins enabled, pause plugin enabled' => [
                'enabledPlugins' => ['DUMMY_PLUGIN_1', 'DUMMY_PLUGIN_2', 'blurPause'],
                'expectedResult' => true,
            ]
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

    /**
     * @param array $plugins
     * @return array
     */
    protected function getPluginsMocks(array $plugins)
    {
        $pluginsMocks = [];
        foreach ($plugins as $pluginId) {
            $pluginMock = $this->createMock(TestPlugin::class);
            $pluginMock->method('getId')
                ->willReturn($pluginId);

            $pluginsMocks[$pluginId] = $pluginMock;
        }

        return $pluginsMocks;
    }
}

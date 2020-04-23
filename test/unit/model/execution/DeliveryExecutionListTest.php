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
 */
namespace oat\taoProctoring\test\unit\model\execution;

use common_ext_Extension;
use common_ext_ExtensionsManager;
use oat\generis\model\data\Model;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\service\ApplicationService;
use oat\taoProctoring\model\execution\DeliveryExecutionList;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoQtiTest\models\SessionStateService;
use oat\generis\test\MockObject;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class DeliveryHelperServiceTest
 * @author Bartlomiej Marszal
 */
class DeliveryExecutionListTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface
     */
    private $serviceLocatorMock;

    /**
     * @var SessionStateService|MockObject
     */
    private $sessionStateServiceMock;
    /**
     * @var common_ext_ExtensionsManager|MockObject
     */
    private $extensionManagerMock;

    /**
     * @var TestSessionConnectivityStatusService|MockObject
     */
    private $testSessionConnectivityStatusServiceMock;

    /**
     * @var DeliveryExecutionManagerService|MockObject
     */
    private $deliveryExecutionManagerServiceMock;

    /**
     * @var common_ext_Extension|MockObject
     */
    private $proctoringExtensionMock;

    /**
     * @var ApplicationService|MockObject
     */
    private $applicationServiceMock;

    /**
     * @var Ontology|MockObject
     */
    private $modelMock;

    /**
     * @var \core_kernel_classes_Property|MockObject
     */
    private $propertyMock;

    /**
     * @var array
     */
    private $deliveryExecution;

    public function setUp(): void
    {
        $this->sessionStateServiceMock = $this->createMock(SessionStateService::class);
        $this->extensionManagerMock = $this->createMock(common_ext_ExtensionsManager::class);
        $this->testSessionConnectivityStatusServiceMock = $this->createMock(TestSessionConnectivityStatusService::class);
        $this->deliveryExecutionManagerServiceMock = $this->createMock(DeliveryExecutionManagerService::class);
        $this->applicationServiceMock = $this->createMock(ApplicationService::class);
        $this->proctoringExtensionMock = $this->createMock(common_ext_Extension::class);
        $this->modelMock = $this->createMock(Ontology::class);
        $this->propertyMock = $this->createMock(\core_kernel_classes_Property::class);
        $this->deliveryExecution = [
            'test_taker' => 'some test taker',
            'delivery_execution_id' => 'delivery_id_string',
            'delivery_id' => 'delivery_id_string',
            'delivery_name' => 'delivery_name_string',
            'start_time' => '1567508223.829546',
        ];

        $this->serviceLocatorMock = $this->getServiceLocatorMock([
            SessionStateService::SERVICE_ID => $this->sessionStateServiceMock,
            common_ext_ExtensionsManager::SERVICE_ID => $this->extensionManagerMock,
            TestSessionConnectivityStatusService::SERVICE_ID => $this->testSessionConnectivityStatusServiceMock,
            ApplicationService::SERVICE_ID => $this->applicationServiceMock,
            DeliveryExecutionManagerService::SERVICE_ID => $this->deliveryExecutionManagerServiceMock
        ]);

        $this->extensionManagerMock->method('getExtensionById')->willReturn($this->proctoringExtensionMock);
    }

    public function testAdjustDeliveryExecutionsFinished()
    {
        $this->deliveryExecution['status'] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished';
        $this->deliveryExecution['current_assessment_item'] = '{"title":"finished","itemPosition":"1","itemCount":"2"}';
        $deliveryExecutions[] = $this->deliveryExecution;

        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);
        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);
        $this->assertSame('finished', $result[0]['state']['progress']);
    }

    public function testAdjustDeliveryFullExecutionExample()
    {
        $deliveryExecutions[] = $this->getExecutionExample();

        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);

        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);
        $this->assertSame('https://nccersso.taocloud.org/tao.rdf#i15675082249329111', $result[0]['id']);
        $this->assertSame('https://nccersso.taocloud.org/tao.rdf#i1567505985808893', $result[0]['delivery']['uri']);
        $this->assertSame('Delivery of Basic Test (Linear-Individual)', $result[0]['delivery']['label']);
        $this->assertSame('1567508223.829546', $result[0]['start_time']);
        $this->assertFalse($result[0]['allowExtraTime']);
        $this->assertSame('11211775', $result[0]['testTaker']['id']);
        $this->assertSame('Chobanian', $result[0]['testTaker']['test_taker_last_name']);
        $this->assertSame('Debora', $result[0]['testTaker']['test_taker_first_name']);
        $this->assertSame('http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished', $result[0]['state']['status']);
        $this->assertSame('finished', $result[0]['state']['progress']);
    }

    public function testAdjustDeliveryExecutionsOnline()
    {
        $this->deliveryExecution['current_assessment_item'] = '{"title":"finished","itemPosition":"1","itemCount":"2"}';
        $this->deliveryExecution['status'] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished';
        $this->deliveryExecution['last_test_taker_activity'] = '1567508648.2458';

        $deliveryExecutions[] = $this->deliveryExecution;

        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);

        //getTestSessionConnectivityStatusService
        $this->testSessionConnectivityStatusServiceMock->method('hasOnlineMode')->willReturn(true);
        $this->testSessionConnectivityStatusServiceMock->method('isOnline')->willReturn(true);

        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);
        $this->assertSame('1567508648.2458', $result[0]['timer']['lastActivity']);
        $this->assertTrue($result[0]['online']);
    }

    public function testAdjustDeliveryExecutionsUserExtraFields()
    {
        $this->deliveryExecution['test_category'] = 'http://www.nccer.org/testmodel#category_01';
        $this->deliveryExecution['test_taker'] = '12345';
        $this->deliveryExecution['status'] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished';
        $this->deliveryExecution['current_assessment_item'] = '{"title":"finished","itemPosition":"1","itemCount":"2"}';


        $deliveryExecutions[] = $this->deliveryExecution;


        $this->proctoringExtensionMock->method('getConfig')->willReturnOnConsecutiveCalls(
            [
                'test_category' => 'http://www.nccer.org/delivery#inheritedTestCategory',
                'test_taker' => 'https://nccersso.taocloud.org/tao.rdf#i1567503166294083'
            ],
            [
                'testTaker' => [
                    'columnPosition' => 1,
                    'filterable' => true
                ]
            ]
        );

        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);
        $deliveryHelperService->setModel($this->modelMock);
        $this->modelMock->method('getProperty')->willReturn($this->propertyMock);
        $categoryResourceMock = $this->createMock(\core_kernel_classes_Resource::class);
        $categoryResourceMock->method('getLabel')->willReturn('CategoryLabelString');
        $this->modelMock->method('getResource')->with('http://www.nccer.org/testmodel#category_01')->willReturn($categoryResourceMock);
        $this->propertyMock->method('getLabel')->willReturn('labelString');

        //Execute
        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);
        $this->assertSame('CategoryLabelString', $result[0]['extraFields']['test_category']);
        $this->assertSame('12345', $result[0]['extraFields']['test_taker']);
    }

    public function testAdjustDeliveryExecutionsProgressStringWithNoOption()
    {
        $this->deliveryExecution['current_assessment_item'] = '{"title":"in progress","itemPosition":"1","itemCount":"2"}';
        $this->deliveryExecution['status'] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive';
        $deliveryExecutions[] = $this->deliveryExecution;

        $this->sessionStateServiceMock->method('hasOption')->willReturn(false);
        $this->proctoringExtensionMock->method('getConfig')->willReturn(null);
        /** @var Ontology|MockObject $modelMock */
        $modelMock = $this->createMock(Ontology::class);
        $modelMock->method('getProperty');

        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setModel($modelMock);
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);
        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);
        $this->assertSame('in progress - item 1/2', $result[0]['state']['progress']);

    }

    public function testAdjustDeliveryExecutionsProgressStringWithCustomOption()
    {
        //Prepare
        $this->deliveryExecution['current_assessment_item'] = '{"title":"finished","itemPosition":"1","itemCount":"2"}';
        $this->deliveryExecution['status'] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished';
        $deliveryExecutions[] = $this->deliveryExecution;
        $this->sessionStateServiceMock->method('hasOption')->willReturn(true);
        $this->sessionStateServiceMock->method('getOption')->willReturn('%s');
        $this->proctoringExtensionMock->method('getConfig')->willReturn(null);

        //Execute
        $deliveryHelperService = new DeliveryExecutionList();
        $deliveryHelperService->setServiceLocator($this->serviceLocatorMock);
        /* @noinspection PhpUnhandledExceptionInspection */
        $result = $deliveryHelperService->adjustDeliveryExecutions($deliveryExecutions);

        //Assert
        $this->assertSame('finished', $result[0]['state']['progress']);
    }

    private function getExecutionExample()
    {
        return $executions[] = [
            'delivery_execution_id' => "https://nccersso.taocloud.org/tao.rdf#i15675082249329111",
            'status' => "http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished",
            'current_assessment_item' => '{"title":"finished"}',
            'test_taker' => "11211775",
            'authorized_by' => "28745",
            'start_time' => "1567508223.829546",
            'end_time' => "1567508648.353866",
            'delivery_id' => "https://nccersso.taocloud.org/tao.rdf#i1567505985808893",
            'delivery_name' => "Delivery of Basic Test (Linear-Individual)",
            'delivery_battery_name' => "Delivery of Basic Test (Linear-Individual)",
            'test_taker_first_name' => "Debora",
            'test_taker_last_name' => "Chobanian",
            'organisation' => "556",
            'exam_key' => "E1567508072453141",
            'paper' => "0",
            'module_id' => "1",
            'test_category' => "http://www.nccer.org/testmodel#categoryGeneralAssessment",
            'test_category_name' => "General Assessment",
            'remaining_time' => "",
            'extra_time' => "0",
            'consumed_extra_time' => "0",
            'diff_timestamp' => "0",
            'instructor' => "13830587",
            'score' => "0",
            'pass' => "",
            'last_test_taker_activity' => "1567508648.2458",
            'last_connect' => "-9223372036854775450",
            'allow_extra_time' => false
        ];
    }

}

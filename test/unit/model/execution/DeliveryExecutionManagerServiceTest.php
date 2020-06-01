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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */
declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\execution;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use common_session_Session;
use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionProctoring;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\time\AdjustmentMap;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentService;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use qtism\data\QtiIdentifiable;

class DeliveryExecutionManagerServiceTest extends TestCase
{
    /**
     * @var DeliveryExecution|MockObject
     */
    private $awaitingExecution;
    /**
     * @var DeliveryExecution|MockObject
     */
    private $canceledExecution;
    /**
     * @var DeliveryExecution|MockObject
     */
    private $pausedExecution;
    /**
     * @var TimerAdjustmentServiceInterface|MockObject
     */
    private $timerAdjustmentServiceMock;
    /**
     * @var DeliveryMonitoringData|MockObject
     */
    private $deliveryMonitoringDataMock;
    /**
     * @var DeliveryMonitoringService|MockObject
     */
    private $deliveryMonitoringServiceMock;
    /**
     * @var User|MockObject
     */
    private $userMock;
    /**
     * @var common_session_Session|MockObject
     */
    private $sessionMock;
    /**
     * @var SessionService|MockObject
     */
    private $sessionServiceMock;
    /**
     * @var EventManager|MockObject
     */
    private $eventManagerMock;
    /**
     * @var TestSession|MockObject
     */
    private $testSessionMock;
    /**
     * @var TestSessionService|MockObject
     */
    private $testSessionServiceMock;
    /**
     * @var ServiceProxy|MockObject
     */
    private $serviceProxyMock;
    /**
     * @var QtiTimer|MockObject
     */
    private $qtiTimerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $awaitingState = $this->createMock(core_kernel_classes_Resource::class);
        $awaitingState->method('getUri')->willReturn(DeliveryExecutionProctoring::STATE_AWAITING);

        $canceledState = $this->createMock(core_kernel_classes_Resource::class);
        $canceledState->method('getUri')->willReturn(DeliveryExecutionProctoring::STATE_CANCELED);

        $activeState = $this->createMock(core_kernel_classes_Resource::class);
        $activeState->method('getUri')->willReturn(DeliveryExecutionProctoring::STATE_ACTIVE);

        $pausedState = $this->createMock(core_kernel_classes_Resource::class);
        $pausedState->method('getUri')->willReturn(DeliveryExecutionProctoring::STATE_PAUSED);

        $authorizedState = $this->createMock(core_kernel_classes_Resource::class);
        $authorizedState->method('getUri')->willReturn(DeliveryExecutionProctoring::STATE_AUTHORIZED);

        $this->awaitingExecution = $this->createMock(DeliveryExecution::class);
        $this->awaitingExecution->method('getState')->willReturn($awaitingState);
        $this->awaitingExecution->method('getIdentifier')->willReturn('awaiting');

        $this->canceledExecution = $this->createMock(DeliveryExecution::class);
        $this->canceledExecution->method('getState')->willReturn($canceledState);
        $this->canceledExecution->method('getIdentifier')->willReturn('canceled');

        $this->pausedExecution = $this->createMock(DeliveryExecution::class);
        $this->pausedExecution->method('getState')->willReturn($pausedState);
        $this->pausedExecution->method('getIdentifier')->willReturn('paused');

        $this->timerAdjustmentServiceMock = $this->createMock(TimerAdjustmentServiceInterface::class);
        $this->deliveryMonitoringDataMock = $this->createMock(DeliveryMonitoringData::class);
        $this->deliveryMonitoringServiceMock = $this->createMock(DeliveryMonitoringService::class);
        $this->userMock = $this->createMock(User::class);
        $this->sessionMock = $this->createMock(common_session_Session::class);
        $this->sessionServiceMock = $this->createMock(SessionService::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->testSessionMock = $this->createMock(TestSession::class);
        $this->testSessionServiceMock = $this->createMock(TestSessionService::class);
        $this->serviceProxyMock = $this->createMock(ServiceProxy::class);
        $this->qtiTimerMock = $this->createMock(QtiTimer::class);
    }

    /**
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     */
    public function testAdjustTimers(): void
    {
        $deliveryExecutions = [
            $this->awaitingExecution,
            $this->canceledExecution,
            $this->pausedExecution,
        ];

        $this->timerAdjustmentServiceMock->method('increase')->willReturn(true);
        $this->timerAdjustmentServiceMock->method('decrease')->willReturn(true);
        $this->deliveryMonitoringDataMock->method('updateData')->with([DeliveryMonitoringService::REMAINING_TIME]);
        $this->deliveryMonitoringServiceMock->method('getData')->willReturn($this->deliveryMonitoringDataMock);
        $this->sessionMock->method('getUser')->willReturn($this->userMock);
        $this->sessionServiceMock->method('getCurrentSession')->willReturn($this->sessionMock);

        $self = $this;
        $awaitingExecution = $this->awaitingExecution;
        $userMock = $this->userMock;
        $this->eventManagerMock->method('trigger')
            ->willReturnCallback(
                static function (DeliveryExecutionTimerAdjusted $event)
                    use ($self, $awaitingExecution, $userMock) {
                        $self->assertSame(['reasons'], $event->getReason());
                        $self->assertSame($awaitingExecution, $event->getDeliveryExecution());
                        $self->assertSame(1, $event->getSeconds());
                        $self->assertSame($userMock, $event->getProctor());
                    });

        $this->testSessionServiceMock->method('getTestSession')->willReturn($this->testSessionMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            TimerAdjustmentServiceInterface::SERVICE_ID => $this->timerAdjustmentServiceMock,
            DeliveryMonitoringService::SERVICE_ID => $this->deliveryMonitoringServiceMock,
            SessionService::SERVICE_ID => $this->sessionServiceMock,
            EventManager::SERVICE_ID => $this->eventManagerMock,
            TestSessionService::SERVICE_ID => $this->testSessionServiceMock,
        ]);

        $service = new DeliveryExecutionManagerService();
        $service->setServiceLocator($serviceLocatorMock);

        $service->adjustTimers($deliveryExecutions, 1, ['reasons']);
    }

    public function testAdjustedTimeWithoutTestSession(): void {
        $this->serviceProxyMock
            ->expects($this->once())
            ->method('getDeliveryExecution')
            ->willReturn($this->pausedExecution);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $this->serviceProxyMock,
            TestSessionService::SERVICE_ID => $this->testSessionServiceMock,
        ]);
        $service = new DeliveryExecutionManagerService();
        $service->setServiceLocator($serviceLocatorMock);
        $this->assertSame(0, $service->getAdjustedTime('PHPUnitDeliveryExecutionId'));
    }

    public function testAdjustedTime(): void
    {
        $item = $this->createMock(QtiIdentifiable::class);
        $item->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('item');

        $section = $this->createMock(QtiIdentifiable::class);
        $section->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('section');
        $testPart = $this->createMock(QtiIdentifiable::class);
        $testPart->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('testPart');
        $test = $this->createMock(QtiIdentifiable::class);
        $test->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('test');

        $this->serviceProxyMock
            ->expects($this->once())
            ->method('getDeliveryExecution')
            ->willReturn($this->pausedExecution);

        $this->timerAdjustmentServiceMock
            ->expects($this->exactly(4))
            ->method('getAdjustment')
            ->willReturnCallback(static function(QtiIdentifiable $part) {
                switch ($part->getIdentifier()) {
                    case 'item':
                        return 10;
                    case 'section':
                        return 15;
                    case 'testPart':
                        return 9; // min - will be used
                    case 'test':
                        return 11;
                }
                return 0; // should never be here
            });

        $this->testSessionMock
            ->expects($this->exactly(4))
            ->method('getTimer')
            ->willReturn($this->qtiTimerMock);

        $this->testSessionMock
            ->expects($this->once())
            ->method('getCurrentAssessmentItemRef')
            ->willReturn($item);

        $this->testSessionMock
            ->expects($this->once())
            ->method('getCurrentAssessmentSection')
            ->willReturn($section);

        $this->testSessionMock
            ->expects($this->once())
            ->method('getCurrentTestPart')
            ->willReturn($testPart);

        $this->testSessionMock
            ->expects($this->once())
            ->method('getAssessmentTest')
            ->willReturn($test);

        $this->testSessionServiceMock->method('getTestSession')->willReturn($this->testSessionMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $this->serviceProxyMock,
            TestSessionService::SERVICE_ID => $this->testSessionServiceMock,
            TimerAdjustmentService::SERVICE_ID => $this->timerAdjustmentServiceMock,
        ]);

        $service = new DeliveryExecutionManagerService();
        $service->setServiceLocator($serviceLocatorMock);
        $this->assertSame(9, $service->getAdjustedTime('PHPUnitDeliveryExecutionId'));
    }
}

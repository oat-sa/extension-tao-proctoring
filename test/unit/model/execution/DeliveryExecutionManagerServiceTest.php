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
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerService;
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
use oat\taoQtiTest\models\runner\time\QtiTimeConstraint;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentServiceInterface;
use qtism\common\datatypes\Duration;

class DeliveryExecutionManagerServiceTest extends TestCase
{
    /**
     * @var DeliveryExecutionManagerService
     */
    private $subject;

    /**
     * @var ServiceProxy|MockObject
     */
    private $serviceProxyMock;

    /**
     * @var TestSessionService|MockObject
     */
    private $testSessionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceProxyMock = $this->createMock(ServiceProxy::class);
        $this->testSessionServiceMock = $this->createMock(TestSessionService::class);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $this->serviceProxyMock,
            TestSessionService::SERVICE_ID => $this->testSessionServiceMock,
            LoggerService::SERVICE_ID => $this->createMock(LoggerService::class),
        ]);

        $this->subject = new DeliveryExecutionManagerService();
        $this->subject->setServiceLocator($serviceLocatorMock);
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
        $service = new DeliveryExecutionManagerService();

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

        $awaitingExecution = $this->createMock(DeliveryExecution::class);
        $awaitingExecution->method('getState')->willReturn($awaitingState);
        $awaitingExecution->method('getIdentifier')->willReturn('awaiting');

        $canceledExecution = $this->createMock(DeliveryExecution::class);
        $canceledExecution->method('getState')->willReturn($canceledState);
        $canceledExecution->method('getIdentifier')->willReturn('canceled');

        $pausedExecution = $this->createMock(DeliveryExecution::class);
        $pausedExecution->method('getState')->willReturn($pausedState);
        $pausedExecution->method('getIdentifier')->willReturn('paused');

        $deliveryExecutions = [
            $awaitingExecution,
            $canceledExecution,
            $pausedExecution,
        ];

        $timerAdjustmentServiceMock = $this->createMock(TimerAdjustmentServiceInterface::class);
        $timerAdjustmentServiceMock->method('increase')->willReturn(true);
        $timerAdjustmentServiceMock->method('decrease')->willReturn(true);

        $deliveryMonitoringDataMock = $this->createMock(DeliveryMonitoringData::class);
        $deliveryMonitoringDataMock->method('updateData')->with([DeliveryMonitoringService::REMAINING_TIME]);

        $deliveryMonitoringServiceMock = $this->createMock(DeliveryMonitoringService::class);
        $deliveryMonitoringServiceMock->method('getData')->willReturn($deliveryMonitoringDataMock);

        $userMock = $this->createMock(User::class);

        $sessionMock = $this->createMock(common_session_Session::class);
        $sessionMock->method('getUser')->willReturn($userMock);

        $sessionServiceMock = $this->createMock(SessionService::class);
        $sessionServiceMock->method('getCurrentSession')->willReturn($sessionMock);

        $eventManagerMock = $this->createMock(EventManager::class);
        $self = $this;
        $eventManagerMock->method('trigger')->willReturnCallback(static function (DeliveryExecutionTimerAdjusted $event) use ($self, $awaitingExecution, $userMock) {
            $self->assertSame(['reasons'], $event->getReason());
            $self->assertSame($awaitingExecution, $event->getDeliveryExecution());
            $self->assertSame(1, $event->getSeconds());
            $self->assertSame($userMock, $event->getProctor());
        });

        $testSessionMock = $this->createMock(TestSession::class);

        $testSessionServiceMock = $this->createMock(TestSessionService::class);
        $testSessionServiceMock->method('getTestSession')->willReturn($testSessionMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            TimerAdjustmentServiceInterface::SERVICE_ID => $timerAdjustmentServiceMock,
            DeliveryMonitoringService::SERVICE_ID => $deliveryMonitoringServiceMock,
            SessionService::SERVICE_ID => $sessionServiceMock,
            EventManager::SERVICE_ID => $eventManagerMock,
            TestSessionService::SERVICE_ID => $testSessionServiceMock,
        ]);

        $service->setServiceLocator($serviceLocatorMock);

        $service->adjustTimers($deliveryExecutions, 1, ['reasons']);
    }

    public function testGetTimerAdjustmentDecreaseLimit_CalculationFailedNoDecreaseLimit(): void
    {
        $expectedLimit = 300;
        $deliveryExecutionId = 'FAKE_ID';

        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $this->serviceProxyMock->method('getDeliveryExecution')
            ->willReturn($deliveryExecutionMock);


        // Setup TestSessionService mock
        $durationMock = $this->createMock(Duration::class);
        $durationMock->method('getSeconds')
            ->willReturn($expectedLimit);
        $qtiTimeConstraintMock = $this->createMock(QtiTimeConstraint::class);
        $qtiTimeConstraintMock->method('getMaximumRemainingTime')
            ->willReturn($durationMock);

        $testSessionMock = $this->createMock(TestSession::class);
        $this->testSessionServiceMock->method('getTestSession')
            ->willReturn($testSessionMock);
        $this->testSessionServiceMock->method('getSmallestMaxTimeConstraint')
            ->willReturn($qtiTimeConstraintMock);

        self::assertSame(
            $expectedLimit,
            $this->subject->getTimerAdjustmentDecreaseLimit($deliveryExecutionId),
            'Method must return correct value of maximum possible time decrease.'
        );
    }

    public function testGetTimerAdjustmentDecreaseLimit(): void
    {
        $expectedLimit = -1;
        $deliveryExecutionId = 'FAKE_ID';

        $this->serviceProxyMock->method('getDeliveryExecution')
            ->willThrowException(new common_Exception('FAKE ERROR MESSAGE'));

        self::assertSame(
            $expectedLimit,
            $this->subject->getTimerAdjustmentDecreaseLimit($deliveryExecutionId),
            'Method must return correct value in case when limit calculation failed.'
        );
    }

    public function testGetTimerAdjustmentIncreaseLimit(): void
    {
        $expectedLimit = -1;
        $deliveryExecutionId = 'FAKE_ID';

        self::assertSame(
            $expectedLimit,
            $this->subject->getTimerAdjustmentIncreaseLimit($deliveryExecutionId),
            'Method must return correct maximum limit for timer increase.'
        );
    }
}

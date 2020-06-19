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
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentService;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentServiceInterface;
use qtism\common\datatypes\Duration;
use qtism\data\QtiIdentifiable;

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
     * @var QtiTimer|MockObject
     */
    private $qtiTimerMock;


    private $loggerServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->loggerServiceMock = $this->createMock(LoggerService::class);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            ServiceProxy::SERVICE_ID => $this->serviceProxyMock,
            TestSessionService::SERVICE_ID => $this->testSessionServiceMock,
            LoggerService::SERVICE_ID => $this->loggerServiceMock,
            TimerAdjustmentService::SERVICE_ID => $this->timerAdjustmentServiceMock,
            EventManager::SERVICE_ID => $this->eventManagerMock,
            SessionService::SERVICE_ID => $this->sessionServiceMock,
            DeliveryMonitoringService::SERVICE_ID => $this->deliveryMonitoringServiceMock,
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
        $awaitingExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_AWAITING,
            'awaiting'
        );
        $canceledExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_CANCELED,
            'canceled'
        );
        $pausedExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_PAUSED,
            'paused'
        );
        $deliveryExecutions = [$awaitingExecution,$canceledExecution, $pausedExecution];

        $this->timerAdjustmentServiceMock->method('increase')->willReturn(true);
        $this->timerAdjustmentServiceMock->method('decrease')->willReturn(true);
        $this->deliveryMonitoringDataMock->method('updateData')->with([DeliveryMonitoringService::REMAINING_TIME]);
        $this->deliveryMonitoringServiceMock->method('getData')->willReturn($this->deliveryMonitoringDataMock);
        $this->sessionMock->method('getUser')->willReturn($this->userMock);
        $this->sessionServiceMock->method('getCurrentSession')->willReturn($this->sessionMock);

        $self = $this;
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

        $this->subject->adjustTimers($deliveryExecutions, 1, ['reasons']);
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

    public function testGetTimerAdjustmentDecreaseLimit_NullSmallestMaxTime(): void
    {
        $expectedLimit = -1;
        $deliveryExecutionId = 'FAKE_ID';

        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $this->serviceProxyMock->method('getDeliveryExecution')
            ->willReturn($deliveryExecutionMock);


        $testSessionMock = $this->createMock(TestSession::class);
        $this->testSessionServiceMock->method('getTestSession')
            ->willReturn($testSessionMock);
        $this->testSessionServiceMock->method('getSmallestMaxTimeConstraint')
            ->willReturn(null);

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

    public function testAdjustedTimeWithoutTestSession(): void {
        $pausedExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_PAUSED,
            'paused'
        );

        $this->serviceProxyMock
            ->expects($this->once())
            ->method('getDeliveryExecution')
            ->willReturn($pausedExecution);

        $this->assertSame(0, $this->subject->getAdjustedTime('PHPUnitDeliveryExecutionId'));
    }

    public function testAdjustedTimeWithoutTimer(): void
    {
        $pausedExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_PAUSED,
            'paused'
        );

        $this->serviceProxyMock
            ->expects($this->once())
            ->method('getDeliveryExecution')
            ->willReturn($pausedExecution);

        $this->testSessionServiceMock
            ->expects($this->once())
            ->method('getTestSession')
            ->willReturn($this->testSessionMock);

        $this->assertSame(0, $this->subject->getAdjustedTime('PHPUnitDeliveryExecutionId'));
    }

    /**
     * @throws QtiTestExtractionFailedException
     */
    public function testAdjustedTime(): void
    {
        $item = $this->createMock(QtiIdentifiable::class);
        $pausedExecution = $this->getDeliveryExecutionMockWithState(
            DeliveryExecutionProctoring::STATE_PAUSED,
            'paused'
        );

        $this->serviceProxyMock
            ->expects($this->once())
            ->method('getDeliveryExecution')
            ->willReturn($pausedExecution);

        $this->timerAdjustmentServiceMock
            ->expects($this->once())
            ->method('getAdjustmentByType')
            ->willReturn(9);

        $this->testSessionServiceMock
            ->expects($this->once())
            ->method('getTestSession')
            ->willReturn($this->testSessionMock);

        $qtiTimeConstrainMock = $this->createMock(QtiTimeConstraint::class);
        $qtiTimeConstrainMock
            ->expects($this->once())
            ->method('getSource')
            ->willReturn($item);
        $qtiTimeConstrainMock
            ->expects($this->once())
            ->method('getTimer')
            ->willReturn($this->qtiTimerMock);
        $this->testSessionServiceMock
            ->expects($this->once())
            ->method('getSmallestMaxTimeConstraint')
            ->willReturn($qtiTimeConstrainMock);

        $this->assertSame(9, $this->subject->getAdjustedTime('PHPUnitDeliveryExecutionId'));
    }

    /**
     * @param string $stateUri
     * @param string $deliveryIdentifier
     * @return DeliveryExecution|MockObject
     */
    private function getDeliveryExecutionMockWithState(string $stateUri, string $deliveryIdentifier): DeliveryExecution
    {
        $executionStateMock = $this->createMock(core_kernel_classes_Resource::class);
        $executionStateMock->method('getUri')->willReturn($stateUri);

        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $deliveryExecutionMock->method('getState')->willReturn($executionStateMock);
        $deliveryExecutionMock->method('getIdentifier')->willReturn($deliveryIdentifier);

        return $deliveryExecutionMock;
    }
}

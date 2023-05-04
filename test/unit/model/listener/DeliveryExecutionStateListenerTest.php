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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\listener;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\listener\DeliveryExecutionStateListener;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\TestSessionService;

class DeliveryExecutionStateListenerTest extends TestCase
{
    /**
     * @var DeliveryExecutionStateListener
     */
    private $subject;

    /**
     * @var TestSessionService|MockObject
     */
    private $testSessionServiceMock;

    /** @var DeliveryMonitoringService|MockObject */
    private $deliveryMonitoringServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testSessionServiceMock = $this->createMock(TestSessionService::class);
        $this->testSessionServiceMock
            ->method('getTestSession')
            ->willReturn($this->createMock(TestSession::class));

        $this->deliveryMonitoringServiceMock = $this->createMock(DeliveryMonitoringService::class);
        $this->deliveryMonitoringServiceMock
            ->method('getData')
            ->willReturn($this->createMock(DeliveryMonitoringData::class));

        $this->subject = new DeliveryExecutionStateListener();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    DeliveryMonitoringService::SERVICE_ID => $this->deliveryMonitoringServiceMock,
                    TestSessionService::SERVICE_ID => $this->testSessionServiceMock
                ]
            )
        );
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function testUpdateRemainingTime_WhenNewDeliveryExecutionStateIsPaused_ThenRemainingTimeIsRecalculated()
    {
        $this->deliveryMonitoringServiceMock
            ->expects(self::once())
            ->method('save')
            ->willReturn(true);
        $this->subject->updateRemainingTime(
            new DeliveryExecutionState(
                $this->createMock(DeliveryExecution::class),
                DeliveryExecutionInterface::STATE_PAUSED,
                DeliveryExecutionInterface::STATE_ACTIVE
            )
        );
    }
    // phpcs:enable PSR1.Methods.CamelCapsMethodName

    // phpcs:disable PSR1.Methods.CamelCapsMethodName
    public function testUpdateRemainingTime_WhenNewDeliveryExecutionStateIsNotPaused_ThenNoActionsTaken()
    {
        $this->deliveryMonitoringServiceMock
            ->expects(self::never())
            ->method('save');
        $this->subject->updateRemainingTime(
            new DeliveryExecutionState(
                $this->createMock(DeliveryExecution::class),
                DeliveryExecutionInterface::STATE_FINISHED,
                DeliveryExecutionInterface::STATE_ACTIVE
            )
        );
    }
    // phpcs:enable PSR1.Methods.CamelCapsMethodName
}

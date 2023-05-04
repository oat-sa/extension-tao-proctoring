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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\listener;

use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoProctoring\model\listener\MonitoringListener;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\repository\MonitoringRepository;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;

class MonitoringListenerTest extends TestCase
{
    /** @var MonitoringListener */
    private $subject;

    /** @var MonitoringRepository */
    private $monitoringRepository;

    protected function setUp(): void
    {
        $this->monitoringRepository = $this->createMock(MonitoringRepository::class);

        $this->subject = new MonitoringListener();
        $this->subject->setServiceLocator($this->getServiceLocatorMock([
            MonitoringRepository::SERVICE_ID => $this->monitoringRepository,
        ]));
    }

    public function testExecutionCreated(): void
    {
        $deliveryExecution = $this->getDeliveryExecutionFixture();

        $monitoringData = new DeliveryMonitoringData(
            $deliveryExecution,
            []
        );

        $testSessionConnectivityStatusService = $this->createConfiguredMock(
            TestSessionConnectivityStatusService::class,
            ['hasOnlineMode' => false]
        );
        $monitoringData->setServiceLocator($this->getServiceLocatorMock([
            TestSessionConnectivityStatusService::SERVICE_ID => $testSessionConnectivityStatusService,
        ]));

        $this->monitoringRepository->method('createMonitoringData')->willReturn($monitoringData);

        $this->monitoringRepository
            ->method('save')
            ->with($this->callback(
                function (DeliveryMonitoringData $monitoringData) {
                    $data = $monitoringData->get();

                    $this->assertArrayHasKey('status', $data);
                    $this->assertEquals(DeliveryExecutionInterface::STATE_PAUSED, $data['status']);

                    $this->assertArrayHasKey('test_taker', $data);
                    $this->assertEquals('user-identifier', $data['test_taker']);

                    $this->assertArrayHasKey('delivery_id', $data);
                    $this->assertEquals('http://test/deliveryExecutionUri', $data['delivery_id']);

                    $this->assertArrayHasKey('delivery_name', $data);
                    $this->assertEquals('deliveryExecutionUri', $data['delivery_name']);

                    $this->assertArrayHasKey('start_time', $data);
                    $this->assertEquals('019183843', $data['start_time']);

                    $this->assertArrayHasKey('test_taker_first_name', $data);
                    $this->assertEquals('name', $data['test_taker_first_name']);

                    $this->assertArrayHasKey('test_taker_last_name', $data);
                    $this->assertEquals('name', $data['test_taker_last_name']);

                    $this->assertArrayHasKey('last_connect', $data);

                    return true;
                }
            ))
            ->willReturn(true);

        $event = new DeliveryExecutionCreated(
            $deliveryExecution,
            $this->getUserFixture()
        );

        $this->subject->executionCreated($event);

        // MonitoringData has been checked during Repository save()
        $this->assertTrue(true);
    }

    private function getDeliveryExecutionFixture(): DeliveryExecutionInterface
    {
        $state = $this->createConfiguredMock(
            core_kernel_classes_Resource::class,
            ['getUri' => DeliveryExecutionInterface::STATE_PAUSED,]
        );

        $delivery = $this->createConfiguredMock(
            core_kernel_classes_Resource::class,
            ['getUri' => 'http://test/deliveryExecutionUri', 'getLabel' => 'deliveryExecutionUri',]
        );

        return $this->createConfiguredMock(
            DeliveryExecutionInterface::class,
            [
                'getIdentifier' => 'http://test/deliveryExecution',
                'getState' => $state,
                'getUserIdentifier' => 'user-identifier',
                'getDelivery' => $delivery,
                'getStartTime' => '019183843',
            ]
        );
    }

    private function getUserFixture(): User
    {
        return $this->createConfiguredMock(
            User::class,
            [
                'getIdentifier' => 'toto',
                'getPropertyValues' => ['name']
            ]
        );
    }
}

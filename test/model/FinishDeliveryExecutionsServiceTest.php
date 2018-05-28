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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoProctoring\test\model;

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\FinishDeliveryExecutionsService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

class FinishDeliveryExecutionsServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @throws \common_exception_Error
     */
    public function testExecute()
    {
        $service = $this->getService();

        $this->assertInstanceOf(\common_report_Report::class, $service->execute());
    }

    /**
     * @return FinishDeliveryExecutionsService
     */
    protected function getService()
    {
        $service = $this->getMockBuilder(FinishDeliveryExecutionsService::class)
            ->setMethods(['getDeliveryMonitoringService','getDeliveryStateService', 'getServiceProxy', 'getDeliveryLog' ,'getTtlAsActive'])
            ->getMockForAbstractClass();
        $service
            ->method('getDeliveryMonitoringService')
            ->willReturn($this->mockDeliveryMonitoringService());

        $service
            ->method('getDeliveryStateService')
            ->willReturn($this->getMockForAbstractClass(DeliveryExecutionStateService::class));

        $service
            ->method('getServiceProxy')
            ->willReturn($this->mockServiceProxy());

        $service
            ->method('getDeliveryLog')
            ->willReturn($this->mockDeliveryLog());

        $service
            ->method('getTtlAsActive')
            ->willReturn('PT5S');

        return $service;
    }

    protected function mockDeliveryLog()
    {
        $service = $this->getMockForAbstractClass(DeliveryLog::class);

        $service
            ->method('get')
            ->willReturn([
                [
                    'created_by' => 'user1',
                    'created_at' => '1517788800',
                ],
                [
                    'created_by' => 'user1',
                    'created_at' => '1527519297',
                ]
            ]);

        return $service;
    }

    protected function mockDeliveryMonitoringService()
    {
        $service = $this->getMockBuilder(DeliveryMonitoringService::class)->disableOriginalConstructor()->getMock();

        $service
            ->method('find')
            ->willReturn( [
                $this->getDeliveryExecMock(['delivery_execution_id' => 'execution id 1']),
                $this->getDeliveryExecMock(['delivery_execution_id' => 'execution id 2']),
            ]);

        return $service;
    }

    protected function mockServiceProxy()
    {
        $service = $this->getMockBuilder(ServiceProxy::class)->disableOriginalConstructor()->getMock();

        $deliveryExecMock = $this->getMockBuilder(DeliveryExecution::class)->disableOriginalConstructor()->getMock();

        $deliveryExecMock
            ->method('getUserIdentifier')
            ->willReturn('user1');

        $service
            ->method('getDeliveryExecution')
            ->willReturn($deliveryExecMock);

        return $service;
    }

    protected function getDeliveryExecMock($seed)
    {
        $mock = $this->getMockForAbstractClass(DeliveryMonitoringData::class);
        $mock
            ->method('get')
            ->willReturn($seed);

        return $mock;
    }
}

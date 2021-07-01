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
 * Copyright (c) 2018-2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model;

use common_persistence_SqlPersistence;
use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\repository\MonitoringRepository;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\generis\persistence\PersistenceManager;

class DeliveryMonitoringStorageTest extends TestCase
{
    /** @var MonitoringRepository */
    private $subject;

    /** @var common_persistence_SqlPersistence $persistence */
    private $persistence;

    protected function setUp(): void
    {
        $this->persistence = $this->getSqlMock('monitoring')->getPersistenceById('monitoring');

        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager
            ->method('getPersistenceById')
            ->with('monitoring')
            ->willReturn($this->persistence);

        $dbSetup = new DbSetup();
        $dbSetup->generateTable($this->persistence);

        $this->subject = new MonitoringRepository([
            MonitoringRepository::OPTION_PERSISTENCE => 'monitoring',
            MonitoringRepository::OPTION_USE_UPDATE_MULTIPLE => false,
            MonitoringRepository::OPTION_PRIMARY_COLUMNS => $dbSetup->getPrimaryColumns()
        ]);
        $this->subject->setServiceLocator($this->getServiceLocatorMock([
            PersistenceManager::SERVICE_ID => $persistenceManager
        ]));
    }

    protected function tearDown(): void
    {
        $this->persistence->query('DROP TABLE delivery_monitoring');
    }

    public function testCreateMonitoringData(): DeliveryMonitoringData
    {
        $data = $this->subject->getData($this->getDeliveryExecutionFixture());
        $this->assertInstanceOf(DeliveryMonitoringData::class, $data);

        return $data;
    }

    /**
     * @depends testCreateMonitoringData
     */
    public function testSave(DeliveryMonitoringData $data): void
    {
        $data->update('a', '1');
        $data->update('b', '2');
        $data->update(DeliveryMonitoringService::STATUS, DeliveryExecutionInterface::STATE_PAUSED);
        $this->assertTrue($this->subject->save($data));

        $dataArray = $data->get();
        $this->assertArrayHasKey('a', $dataArray);
        $this->assertArrayHasKey('b', $dataArray);
        $this->assertEquals('1', $dataArray['a']);
        $this->assertEquals('2', $dataArray['b']);
    }

    /**
     * @depends testCreateMonitoringData
     */
    public function testPartialSave(DeliveryMonitoringData $data): void
    {
        $data->update('a', '3');
        $this->assertTrue($this->subject->partialSave($data));

        $dataArray = $data->get();
        $this->assertArrayHasKey('a', $dataArray);
        $this->assertEquals('3', $dataArray['a']);

        $data->update('a', '4');
        $this->assertTrue($this->subject->partialSave($data));

        $dataArray = $data->get();
        $this->assertArrayHasKey('a', $dataArray);
        $this->assertEquals('4', $dataArray['a']);
    }

    private function getDeliveryExecutionFixture(): DeliveryExecutionInterface
    {
        $state = $this->createConfiguredMock(
            core_kernel_classes_Resource::class,
            ['getUri' => DeliveryExecutionInterface::STATE_PAUSED]
        );

        return $this->createConfiguredMock(
            DeliveryExecutionInterface::class,
            [
                'getIdentifier' => 'http://test/deliveryExecution',
                'getState' => $state,
            ]
        );
    }
}

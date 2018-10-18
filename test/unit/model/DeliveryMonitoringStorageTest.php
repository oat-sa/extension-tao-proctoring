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

namespace oat\taoProctoring\test\unit\model;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\generis\test\TestCase;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;

/**
 * class DeliveryMonitoringStorageTest
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryMonitoringStorageTest extends TestCase
{
    public function testPartialSave()
    {
        $pm = $this->getSqlMock('monitoring');
        DbSetup::generateTable($pm->getPersistenceById('monitoring'));
        $sl = $this->getServiceLocatorMock([\common_persistence_Manager::SERVICE_ID => $pm]);
        $deP = $this->prophesize(DeliveryExecution::class);
        $deP->getIdentifier()->willReturn('http://test/deliveryExecution');

        $stateProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $stateProphecy->getUri()->willReturn(DeliveryExecution::STATE_PAUSED);

        $deP->getState()->willReturn($stateProphecy);
        $de = $deP->reveal();
        $storage = new MonitoringStorage([
            MonitoringStorage::OPTION_PERSISTENCE => 'monitoring',
            'use_update_multiple' => false,
            'primary_columns' => DbSetup::getPrimaryColumns()
        ]);
        $storage->setServiceLocator($sl);
        
        // full save
        $data = $storage->getData($de);
        $this->assertInstanceOf(DeliveryMonitoringData::class, $data);
        $data->update('a', '1');
        $data->update('b', '2');
        $data->update(DeliveryMonitoringService::STATUS, DeliveryExecution::STATE_PAUSED);
        $success = $storage->save($data);
        $this->assertTrue($success);
        
        // partial save
        $data2 = $storage->createMonitoringData($de);
        $data2->update('a', '3');
        $success = $storage->partialSave($data2);
        $this->assertTrue($success);

        // load and compare data
        $data3 = $storage->getData($de);
        $this->assertInstanceOf(DeliveryMonitoringData::class, $data3);
        $dataArray = $data3->get();
        $this->assertArrayHasKey('a', $dataArray);
        $this->assertEquals('3', $dataArray['a']);
        $this->assertEquals('2', $dataArray['b']);
    }
}

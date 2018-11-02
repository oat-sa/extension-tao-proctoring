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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\test\integration\monitorCache;

require_once dirname(__FILE__).'/../../../../tao/includes/raw_start.php';

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\test\integration\monitorCache\helpers\MonitorCacheServiceThread;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;

// @todo fix "Service "taoProctoring/DeliveryMonitoring" not found"

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class MonitorCacheServiceTest extends DeliveryMonitoringServiceTest
{

    public function setUp()
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        TaoPhpUnitTestRunner::initTest();

        $service = ServiceManager::getServiceManager()->get(MonitorCacheService::CONFIG_ID);
        $this->service = new MonitorCacheService($service->getOptions());
        $this->persistence = \common_persistence_Manager::getPersistence('default');

        if (!extension_loaded('pthreads')) {
            $this->markTestSkipped(
                'Pthreads extension is not available.'
            );
        }
    }

    /**
     * Test concurrent queries to the storage
     */
    public function testSaveConcurrent()
    {
        $fails = 0;
        $service = $this->service;
        for ($i = 0; $i < 100; $i++) {
            $workers[$i]= new MonitorCacheServiceThread($service);
            $workers[$i]->start(0);
        }
        foreach ($workers as $worker) {
            $worker->join();
            if ($worker->isFailed()) {
                $fails++;
            }
        }
        $this->assertEquals(0, $fails);
    }
}
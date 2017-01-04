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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\scripts\install\db\DbSetup;
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

/**
 * Setup the tables and the service to cache
 * delivery data to allow monitoring
 */
class SetupDeliveryMonitoring extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $pm = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $pm->getPersistenceById('default');
        DbSetup::generateTable($persistence);
        $this->registerService(DeliveryMonitoringService::SERVICE_ID, new MonitorCacheService(array(
            MonitorCacheService::OPTION_PERSISTENCE => 'default',
            MonitorCacheService::OPTION_PRIMARY_COLUMNS => DbSetup::getPrimaryColumns()
        )));
    }
}


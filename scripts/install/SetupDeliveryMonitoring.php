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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\repository\MonitoringRepository;
use oat\taoProctoring\scripts\install\db\DbSetup;

/**
 * Setup the tables and the service to enable delivery data to allow monitoring
 */
class SetupDeliveryMonitoring extends InstallAction
{
    public function __invoke($params)
    {
        try {
            $service = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        } catch (ServiceNotFoundException $exception) {
            $service = new MonitoringRepository(array(
                MonitoringRepository::OPTION_PERSISTENCE => 'default',
                MonitoringRepository::OPTION_USE_UPDATE_MULTIPLE => false
            ));
            $this->propagate($service);
        }

        $dbSetup = new DbSetup();
        $dbSetup->generateTable($service->getPersistence());

        $service->setOption(MonitoringRepository::OPTION_PRIMARY_COLUMNS, $dbSetup->getPrimaryColumns());
        $this->registerService(MonitoringRepository::SERVICE_ID, $service);
    }
}

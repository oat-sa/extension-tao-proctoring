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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\update;

use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\oatbox\action\Action;
use \common_report_Report as Report;

/**
 * Class UpdateLastConnectivity
 *
 * run example:
 * ```
 * sudo -u www-data php index.php 'oat\taoProctoring\scripts\update\UpdateLastConnectivity'
 * ```
 * @package oat\taoProctoring\scripts\update
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class UpdateLastConnectivity implements Action
{

    public function __invoke($params)
    {
        $serviceManager = ServiceManager::getServiceManager();
        /** @var DeliveryMonitoringService $monitoring */
        $monitoring = $serviceManager->get(DeliveryMonitoringService::SERVICE_ID);
        $data = $monitoring->find();
        $executions = 0;
        foreach ($data as $deliveryData) {
            try {
                $deliveryData->updateData([
                    DeliveryMonitoringService::CONNECTIVITY
                ]);
                $monitoring->partialSave($deliveryData);
                $executions++;
            } catch (\common_exception_NotFound $e) {
                //Delivery execution not found; Skip
            }
        }

        return new Report(
            Report::TYPE_INFO,
            $executions . ' delivery executions updated'
        );
    }
}

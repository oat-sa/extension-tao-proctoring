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

namespace oat\taoProctoring\model\monitorCache\update;

use oat\tao\model\event\MetadataModified;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\oatbox\service\ServiceManager;

/**
 *
 * @package oat\taoProctoring
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */
class DeliveryUpdater
{

    public function changeLabel($service, $deliveryUri, $newLabel)
    {
        $deliveryExecutionsData = $service->find([
            DeliveryMonitoringService::DELIVERY_ID => $deliveryUri,
        ], []);

        foreach ($deliveryExecutionsData as $data) {
            $data->update(DeliveryMonitoringService::DELIVERY_NAME, $newLabel);
            $success = $service->save($data);
            if (!$success) {
                \common_Logger::w('monitor cache for delivery ' . $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID] . ' could not be updated. Label has not been changed');
            }
        }
    }

}
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

namespace oat\taoProctoring\model\execution\Counter;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterService;

/**
 * Class DeliveryExecutionCounterService
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class KVDeliveryExecutionCounterService extends DeliveryExecutionCounterService
{
    /**
     * @param $statusUri
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function refresh($statusUri)
    {
        $persistence = $this->getPersistence();
        /** @var DeliveryMonitoringService $deliveryMonitoring */
        $deliveryMonitoring = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $newValue = $deliveryMonitoring->count([
            [DeliveryMonitoringService::STATUS => $statusUri],
        ]);
        $persistence->set($this->getStatusKey($statusUri), $newValue);
    }
}

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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;

/**
 * Class DeliveryExecutionCounterService
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class DeliveryExecutionCounterService extends ConfigurableService implements DeliveryExecutionCounterInterface
{

    /**
     * @param $statusUri
     * @return int
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function count($statusUri)
    {
        /** @var DeliveryMonitoringService $deliveryMonitoring */
        $deliveryMonitoring = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        return $deliveryMonitoring->count([
            [DeliveryMonitoringService::STATUS => $statusUri],
        ]);
    }

    /**
     * @param DeliveryExecutionState $event
     * @return mixed
     */
    public function executionStateChanged(DeliveryExecutionState $event)
    {
    }

    /**
     * @param DeliveryExecutionCreated $event
     * @return mixed
     */
    public function executionCreated(DeliveryExecutionCreated $event)
    {
    }

    /**
     * @param $statusUri
     */
    public function refresh($statusUri)
    {
    }
}

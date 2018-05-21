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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use \oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoProctoring\model\ActivityMonitoringService;
use oat\taoProctoring\model\execution\Counter\DeliveryExecutionCounterService;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;

/**
 * Action to register necessary extension services
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterServices extends InstallAction
{
    /**
     * @param $params
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $deliveryExecutionStateService = new DeliveryExecutionStateService([
            DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE => 'PT1H',
            DeliveryExecutionStateService::OPTION_CANCELLATION_DELAY => 'PT30M',
            DeliveryExecutionStateService::OPTION_TIME_HANDLING => false,
        ]);
        $this->registerService(StateServiceInterface::SERVICE_ID, $deliveryExecutionStateService);

        $activityMonitoringService = new ActivityMonitoringService([
            ActivityMonitoringService::OPTION_ACTIVE_USER_THRESHOLD => 300,
        ]);
        $this->getServiceManager()->register(
            DeliveryExecutionCounterInterface::SERVICE_ID,
            new DeliveryExecutionCounterService()
        );
        $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $activityMonitoringService);
    }
}

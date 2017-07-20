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

namespace oat\taoProctoring\scripts\update;

use common_ext_ExtensionsManager;
use common_report_Report;
use oat\taoDelivery\model\execution\implementation\KeyValueService;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

/**
 * This one required in order to move from deprecated implementation
 * Class setKVExecutionPersistenceService
 * @package oat\taoProctoring\scripts\update
 */
class setKVExecutionPersistenceService extends \common_ext_action_InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \ReflectionException
     */
    public function __invoke($params)
    {
        $ext = common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        $oldService = $ext->getConfig(ServiceProxy::CONFIG_KEY);
        $persistenceOption = $oldService->getOption($oldService::OPTION_PERSISTENCE);
        $newService = new KeyValueService([
            KeyValueService::OPTION_PERSISTENCE => $persistenceOption,
        ]);


        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $deliveryExecutionsData = $deliveryMonitoringService->find(
            [
                ['start_time' => '>0']
            ]
        );

        $method = new \ReflectionMethod(get_class($newService),
            'addDeliveryToUserExecutionList');
        $method->setAccessible(true);

        foreach ($deliveryExecutionsData as $deliveryExecutionData) {
            $data = $deliveryExecutionData->get();

            $method->invoke($newService,
                $data[DeliveryMonitoringService::TEST_TAKER],
                $data[DeliveryMonitoringService::DELIVERY_ID],
                $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]);

        }

        $ext->setConfig(ServiceProxy::CONFIG_KEY, $newService);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS,
            'Execution KV storage updated to oat\taoDelivery\model\execution\KeyValueService');

    }
}


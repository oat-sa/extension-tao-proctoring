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

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;
use oat\oatbox\service\ServiceManager;

/**
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TestUpdate
{

    public static function testStateChange(QtiTestChangeEvent $event)
    {
        /** @var DeliveryMonitoringService $service */
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
        $data = $service->getData($deliveryExecution, false);
        $data->setTestSession($event->getSession());
        $data->updateData([
            DeliveryMonitoringService::STATUS,
            DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM,
            DeliveryMonitoringService::START_TIME,
            DeliveryMonitoringService::END_TIME,
            DeliveryMonitoringService::REMAINING_TIME,
        ]);
        $success = $service->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }


}

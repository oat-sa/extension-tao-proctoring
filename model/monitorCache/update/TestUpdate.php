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

namespace oat\taoProctoring\model\monitorCache\update;

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;
use oat\oatbox\service\ServiceManager;
use qtism\runtime\tests\AssessmentTestSessionState;

/**
 *
 * @package oat\taoProctoring
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class TestUpdate
{

    public static function testStateChange(QtiTestChangeEvent $event)
    {
        /** @var DeliveryMonitoringService $service */
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
        $data = $service->getData($deliveryExecution, false);

        $dataKeys = [
            DeliveryMonitoringService::STATUS,
        ];

        $session = $event->getSession();
        if ($session->getState() == AssessmentTestSessionState::INTERACTING) {
            $dataKeys[] = DeliveryMonitoringService::DIFF_TIMESTAMP;
            $dataKeys[] = DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY;
        }
        $data->setTestSession($session);
        $data->updateData($dataKeys);
        $success = $service->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }

}

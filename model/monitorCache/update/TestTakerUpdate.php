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

namespace oat\taoProctoring\model\monitorCache\update;

use oat\generis\model\GenerisRdf;
use oat\tao\model\event\MetadataModified;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\oatbox\service\ServiceManager;
use oat\tao\helpers\UserHelper;
use oat\taoTestTaker\models\TestTakerService;

/**
 *
 * @package oat\taoProctoring
 * @author Mikhail Kamarouski <kamarouski@1pt.com>
 */
class TestTakerUpdate
{

    public static function propertyChange(MetadataModified $event)
    {
        $resource = $event->getResource();
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);

        $tracked = array_merge([GenerisRdf::PROPERTY_USER_FIRSTNAME, GenerisRdf::PROPERTY_USER_LASTNAME], array_map(function ($field) {
            return $field['property']->getUri();
        }, DeliveryHelper::getExtraFieldsProperties()));


        if (in_array($event->getMetadataUri(), $tracked) && $resource->hasType(new \core_kernel_classes_Class(TestTakerService::CLASS_URI_SUBJECT))) {

            $deliveryExecutionsData = $service->find([
                DeliveryMonitoringService::TEST_TAKER => $resource->getUri(),
            ], []);
            $user = new \core_kernel_users_GenerisUser($resource);

            foreach ($deliveryExecutionsData as $data) {
                $data->update(DeliveryMonitoringService::TEST_TAKER_FIRST_NAME, UserHelper::getUserFirstName($user));
                $data->update(DeliveryMonitoringService::TEST_TAKER_LAST_NAME, UserHelper::getUserLastName($user));
                $success = $service->partialSave($data);
                if (!$success) {
                    \common_Logger::w('monitor cache for delivery ' . $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID] . ' could not be updated. TestTaker data has not been changed');
                }
            }
        }
    }

}
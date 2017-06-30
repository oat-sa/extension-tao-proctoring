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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 * @author Aleksej Tikhanovich <aleksej@taotedting.com>
 */

namespace oat\taoProctoring\model\execution;

use oat\generis\model\data\ModelManager;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\ProctorService;

class ProctoredDeliveryFactoryEventsService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/deliveryFactoryEvents';

    /**
     * Listen create event for delivery
     * @param Event $event
     */
    public static function create(Event $event)
    {
        $data = $event->jsonSerialize();
        if (!empty($data['delivery'])) {
            /** @var  $delivery */
            $delivery = ModelManager::getModel()->getResource($data['delivery']);
            $property = ServiceManager::getServiceManager()->get(self::SERVICE_ID)->getOption('proctoredByDefault');
            if ($property) {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_ENABLED);
            } else {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_DISABLED);
            }
        }
    }

    /**
     * Listen update event for delivery
     * @param Event $event
     */
    public static function update(Event $event)
    {
        $data = $event->jsonSerialize();
        $deliveryData = !empty($data['data']) ? $data['data'] : [];
        if (!empty($data['delivery'])) {
            $delivery = ModelManager::getModel()->getResource($data['delivery']);
            if (isset($deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) && !$deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_DISABLED);
            }
        }
    }
}

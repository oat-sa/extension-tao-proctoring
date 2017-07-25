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
 */

namespace oat\taoProctoring\model\delivery;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoProctoring\model\ProctorService;

/**
 * Listen service for legacy delivery
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class LegacyDeliverySyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoProctoring/LegacyDeliverySync';

    /**
     * Listen create event for delivery
     * @param DeliveryCreatedEvent $event
     */
    public function onDeliveryCreated(DeliveryCreatedEvent $event)
    {
        $delivery = $this->getResource($event->getDeliveryUri());
        $proctored = $this->getOption(TestTakerAuthorizationService::PROCTORED_BY_DEFAULT);
        $delivery->editPropertyValues($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR), (
            $proctored ? ProctorService::ACCESSIBLE_PROCTOR_ENABLED : ProctorService::ACCESSIBLE_PROCTOR_DISABLED
        ));
    }

    /**
     * Listen update event for delivery
     * @param DeliveryUpdatedEvent $event
     */
    public function onDeliveryUpdated(DeliveryUpdatedEvent $event)
    {
        $data = $event->jsonSerialize();
        $deliveryData = !empty($data['data']) ? $data['data'] : [];
        $delivery = $this->getResource($event->getDeliveryUri());
        if (isset($deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) && !$deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) {
            $delivery->editPropertyValues($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_DISABLED);
        }
    }
}

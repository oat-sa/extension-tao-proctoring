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
 * Listen service for delivery events.
 * Checking current state of http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible and setting correct value.
 *
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
class DeliverySyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoProctoring/DeliverySync';

    const PROCTORED_BY_DEFAULT = 'proctored_by_default';

    /**
     * Listen create event for delivery
     * @param DeliveryCreatedEvent $event
     */
    public function onDeliveryCreated(DeliveryCreatedEvent $event)
    {
        $delivery = $this->getResource($event->getDeliveryUri());

        $proctoredByDefault = $this->isProctoredByDefault();

        $delivery->editPropertyValues($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR), (
        $proctoredByDefault ? ProctorService::ACCESSIBLE_PROCTOR_ENABLED : ProctorService::ACCESSIBLE_PROCTOR_DISABLED
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

    /**
     * Whenever or not new deliveries should be proctored by default
     * @param boolean $proctored
     * @return $this
     */
    public function setProctoredByDefault($proctored)
    {
        $this->setOption(self::PROCTORED_BY_DEFAULT, $proctored);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isProctoredByDefault()
    {
        return $this->hasOption(self::PROCTORED_BY_DEFAULT)
            ? $this->getOption(self::PROCTORED_BY_DEFAULT)
            : true;
    }
}

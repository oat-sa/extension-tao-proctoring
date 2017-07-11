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
namespace oat\taoProctoring\model;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\user\User;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

/**
 * Sample Delivery Service for proctoring
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class ProctorService extends ConfigurableService implements ProctorServiceInterface
{
    use OntologyAwareTrait;

    const ROLE_PROCTOR = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole';

    const ACCESSIBLE_PROCTOR = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible';

    const ACCESSIBLE_PROCTOR_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    const ACCESSIBLE_PROCTOR_DISABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyDisabled';

    const PROCTORED_BY_DEFAULT = 'proctored_by_default';


    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @return array
     */
    public function getProctorableDeliveries(User $proctor, $context = null)
    {
        return DeliveryAssemblyService::singleton()->getRootClass()->searchInstances(
            array(self::ACCESSIBLE_PROCTOR => self::ACCESSIBLE_PROCTOR_ENABLED), array('recursive' => true)
        );
    }

    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $criteria = $this->getCriteria($delivery, $context, $options);
        $options['asArray'] =  true;
        return $monitoringService->find($criteria, $options, true);
    }

    public function countProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $criteria = $this->getCriteria($delivery, $context, $options);
        return $monitoringService->count($criteria, $options);
    }


    /**
     * @param null $delivery
     * @param null $context
     * @param array $options
     * @return array
     */
    protected function getCriteria($delivery = null, $context = null, $options = [])
    {
        $criteria = [];
        if ($delivery !== null) {
            $criteria = [
                [DeliveryMonitoringService::DELIVERY_ID => $delivery->getUri()]
            ];
        }

        if (isset($options['filters']) && $options['filters']) {
            $criteria = array_merge($options['filters'], $criteria);
        }

        return $criteria;
    }

    /**
     * Listen create event for delivery
     * @param DeliveryCreatedEvent $event
     */
    public function listenCreateDeliveryEvent(DeliveryCreatedEvent $event)
    {
        $data = $event->jsonSerialize();
        if (!empty($data['delivery'])) {
            /** @var  $delivery */
            $delivery = $this->getResource($data['delivery']);
            $property = $this->getOption(self::PROCTORED_BY_DEFAULT);
            if ($property) {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_ENABLED);
            } else {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_DISABLED);
            }
        }
    }

    /**
     * Listen update event for delivery
     * @param DeliveryUpdatedEvent $event
     */
    public function listenUpdateDeliveryEvent(DeliveryUpdatedEvent $event)
    {
        $data = $event->jsonSerialize();
        $deliveryData = !empty($data['data']) ? $data['data'] : [];
        if (!empty($data['delivery'])) {
            $delivery = $this->getResource($data['delivery']);
            if (isset($deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) && !$deliveryData[ProctorService::ACCESSIBLE_PROCTOR]) {
                $delivery->editPropertyValues(new \core_kernel_classes_Property(ProctorService::ACCESSIBLE_PROCTOR), ProctorService::ACCESSIBLE_PROCTOR_DISABLED);
            }
        }
    }

    /**
     * By default used only one ProctorService
     * But when ProctorService extended and has many implementations
     * then ProctorServiceRoute will determine which ProctorService should be used in the current context
     * @return bool
     */
    public function isSuitable()
    {
        return true;
    }

}

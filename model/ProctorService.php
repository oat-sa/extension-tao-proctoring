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
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

/**
 * Sample Delivery Service for proctoring
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class ProctorService extends ConfigurableService implements ProctorServiceHandler
{
    use OntologyAwareTrait;

    /**
     * @deprecated constant moved to the \oat\taoProctoring\model\authorization\TestTakerAuthorizationService
     */
    const PROCTORED_BY_DEFAULT = 'proctored_by_default';

    const ROLE_PROCTOR = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole';

    const ROLE_PROCTOR_ADMINISTRATOR = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorAdministratorRole';

    const ACCESSIBLE_PROCTOR = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible';

    const ACCESSIBLE_PROCTOR_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    const ACCESSIBLE_PROCTOR_DISABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyDisabled';

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

        if (!empty($options['filters']) && is_array($options['filters'])) {
            $criteria = array_merge($options['filters'], $criteria);
        }

        return $criteria;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceHandler::isSuitable()
     */
    public function isSuitable(User $user, $deliveryId = null)
    {
        return true;
    }

}

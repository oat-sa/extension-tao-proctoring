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
namespace oat\taoProctoring\model\delivery;

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
class DeliveryService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoProctoring/delivery';
    
    const ROLE_PROCTOR = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole';

    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @return array
     */
    public function getProctorableDeliveries(User $proctor, $context = null)
    {
        return DeliveryAssemblyService::singleton()->getAllAssemblies();
    }
    
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null)
    {
        $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $criteria = [
            [DeliveryMonitoringService::DELIVERY_ID => $delivery->getUri()]
        ];
        
        $options = ['asArray' => true];
        return $monitoringService->find($criteria, $options, true);
    }
    

}

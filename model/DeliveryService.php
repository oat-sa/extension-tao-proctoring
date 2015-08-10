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

use oat\oatbox\user\User;
use oat\oatbox\service\ConfigurableService;
/**
 * Sample Delivery Service for proctoring
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class DeliveryService extends ConfigurableService
{

    public function getProctorableDeliveries(User $proctor) {
        $service = \taoDelivery_models_classes_DeliveryAssemblyService::singleton();
        $allDeliveries = array();
        foreach ($service->getRootClass()->getInstances(true) as $deliveryResource) {
            $allDeliveries[] = new \taoDelivery_models_classes_DeliveryRdf($deliveryResource);
        }
        return $allDeliveries;
    }

    /**
     * 
     * @param string $deliveryId
     * @return \taoDelivery_models_classes_DeliveryRdf
     */
    public function getDelivery($deliveryId) {
        return new \taoDelivery_models_classes_DeliveryRdf($deliveryId);
    }

    /**
     * @param $deliveryId
     * @param array $options
     * @return array
     */
    public function getDeliveryTestTakers($deliveryId, $options = array()) {
        // TODO: get the list of test takers assigned to a particular delivery
        return array();
    }

    /**
     * @param User $proctor
     * @param string $deliveryId
     * @param array $options
     * @return array
     */
    public function getAvailableTestTakers(User $proctor, $deliveryId, $options = array()) {
        // TODO: get the list of available test takers that have not been already assigned to a particular delivery
        return array();
    }

    /**
     * @param $testTakerId
     * @param $deliveryId
     * @return bool
     */
    public function assignTestTaker($testTakerId, $deliveryId) {
        // TODO: assign a test taker to a delivery
        return true;
    }
}

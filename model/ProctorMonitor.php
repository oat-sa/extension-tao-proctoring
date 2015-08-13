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
use oat\taoFrontOffice\model\Delivery;
use taoDelivery_models_classes_execution_DeliveryExecution;

/**
 * Interface for the proctoringservice
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
interface ProctorMonitor
{
    /**
     * Get the deliveries accessible by a proctor
     *
     * @param User $proctor
     * @return Delivery[]
     */
    public function getProctorableDeliveries(User $proctor);

    /**
     * Gets the executions of a delivery
     *
     * @param $deliveryId
     * @return taoDelivery_models_classes_execution_DeliveryExecution[]
     */
    public function getDeliveryExecutions($deliveryId);
    
    /**
     * Get a delivery
     *
     * @param string $deliveryId
     * @return Delivery
     */
    public function getDelivery($deliveryId);

}

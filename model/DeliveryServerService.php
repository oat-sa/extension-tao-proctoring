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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */

namespace oat\taoProctoring\model;

use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\execution\DeliveryExecution;

/**
 * Service to manage the execution of deliveries
 *
 * @access public
 * @author Aleh Hutnikau Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class DeliveryServerService extends \oat\taoDelivery\model\execution\DeliveryServerService
{
    /**
     * Get resumable (active) deliveries.
     * @param User $user User instance. If not given then all deliveries will be returned regardless of user URI.
     * @return DeliveryExecutionInterface[]
     */
    public function getResumableDeliveries(User $user)
    {
        $deliveryExecutionService = ServiceProxy::singleton();
        $userUri = $user->getIdentifier();
        $started = array_merge(
            $deliveryExecutionService->getActiveDeliveryExecutions($userUri),
            $deliveryExecutionService->getPausedDeliveryExecutions($userUri),
            $deliveryExecutionService->getDeliveryExecutionsByStatus($userUri, DeliveryExecution::STATE_AWAITING),
            $deliveryExecutionService->getDeliveryExecutionsByStatus($userUri, DeliveryExecution::STATE_AUTHORIZED)
        );
        $resumable = array();
        foreach ($started as $deliveryExecution) {
            $delivery = $deliveryExecution->getDelivery();
            if ($delivery->exists()) {
                $resumable[] = $deliveryExecution;
            }
        }
        return $resumable;
    }
}

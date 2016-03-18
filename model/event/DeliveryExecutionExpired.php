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
 */

namespace oat\taoProctoring\model\event;

use oat\oatbox\event\Event;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\oatbox\user\User;
/**
 * This event is fired whenever a proctor terminates
 * a delivery execution expired after pausing
 */
class DeliveryExecutionExpired implements Event
{
    /**
     * @var DeliveryExecution
     */
    private $deliveryExecution;
    
    /**
     * @var User
     */
    private $proctor;
    
    /**
     * @var mixed
     */
    private $reason;

    /**
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * DeliveryExecutionExpired constructor.
     * @param DeliveryExecution $deliveryExecution
     * @param User $proctor
     * @param null $reason
     */
    public function __construct(DeliveryExecution $deliveryExecution, User $proctor, $reason = null)
    {
        $this->deliveryExecution = $deliveryExecution;
        $this->proctor = $proctor;
        $this->reason = $reason;
    }

    /**
     * Returns the terminated delivery execution
     * 
     * @return DeliveryExecution
     */
    public function getDeliveryExecution()
    {
        return $this->deliveryExecution;
    }

    /**
     * Returns the reason for termination
     * 
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Returns the proctor that terminated the execution
     * 
     * @return \oat\oatbox\user\User
     */
    public function getProctor()
    {
        return $this->proctor;
    }

}

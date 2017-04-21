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

/**
 * This event is fired whenever a delivery execution goes to the `finished` state
 */
class DeliveryExecutionFinished implements Event
{
    /**
     * @var DeliveryExecution
     */
    private $deliveryExecution;
    
    /**
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * DeliveryExecutionFinished constructor.
     * @param DeliveryExecution $deliveryExecution
     */
    public function __construct(DeliveryExecution $deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
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
}
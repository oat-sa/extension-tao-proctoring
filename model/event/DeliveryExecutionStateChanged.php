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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\event;

use oat\taoDelivery\model\execution\DeliveryExecution;

use oat\oatbox\event\Event;

class DeliveryExecutionStateChanged implements Event
{

    const EVENT_NAME = __CLASS__;

    /**
     * @var DeliveryExecution
     */
    private $deliveryExecution;

    /**
     * @return string
     */
    public function getName()
    {
        return self::EVENT_NAME;
    }

    /**
     * DeliveryExecutionExpired constructor.
     * @param DeliveryExecution $deliveryExecution
     */
    public function __construct(DeliveryExecution $deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
    }

    /**
     * Returns the delivery execution
     *
     * @return DeliveryExecution
     */
    public function getDeliveryExecution()
    {
        return $this->deliveryExecution;
    }
}
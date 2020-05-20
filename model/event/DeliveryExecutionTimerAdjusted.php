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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */
declare(strict_types=1);

namespace oat\taoProctoring\model\event;

use oat\oatbox\event\Event;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution;

class DeliveryExecutionTimerAdjusted implements Event
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
     * @var int
     */
    private $seconds;

    /**
     * DeliveryExecutionTimerAdjusted constructor.
     * @param DeliveryExecution $deliveryExecution
     * @param User $proctor
     * @param int $seconds
     * @param null $reason
     */
    public function __construct(DeliveryExecution $deliveryExecution, User $proctor, int $seconds, $reason = null)
    {
        $this->deliveryExecution = $deliveryExecution;
        $this->proctor = $proctor;
        $this->reason = $reason;
        $this->seconds = $seconds;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return __CLASS__;
    }

    /**
     * Returns the delivery execution
     *
     * @return DeliveryExecution
     */
    public function getDeliveryExecution(): DeliveryExecution
    {
        return $this->deliveryExecution;
    }

    /**
     * Returns the reason
     *
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Returns the proctor
     */
    public function getProctor(): User
    {
        return $this->proctor;
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }
}

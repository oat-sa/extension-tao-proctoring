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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoProctoring\model\Command;

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;

class ProctorCommand implements \JsonSerializable
{
    /** @var DeliveryExecutionInterface */
    private $deliveryExecution;

    /** @var string */
    private $deliveryExecutionId;

    /** @var array */
    private $reason;

    /** @var string */
    private $futureState;

    /** @var string */
    private $testCenter;

    /**
     * ProctorCommand constructor.
     * @param $deliveryExecutionId
     * @param $futureState
     */
    public function __construct($deliveryExecutionId, $futureState)
    {
        $this->deliveryExecutionId = $deliveryExecutionId;
        $this->futureState = $futureState;
    }

    /**
     * @return string
     */
    public function getDeliveryExecutionId()
    {
        return $this->deliveryExecutionId;
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     */
    public function setDeliveryExecution($deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
    }

    /**
     * @return DeliveryExecutionInterface
     */
    public function getDeliveryExecution()
    {
        return $this->deliveryExecution;
    }

    /**
     * @param array $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return array
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param mixed $testCenter
     */
    public function setTestCenter($testCenter)
    {
        $this->testCenter = $testCenter;
    }

    /**
     * @return mixed
     */
    public function getTestCenter()
    {
        return $this->testCenter;
    }

    /**
     * @return string
     */
    public function getFutureState()
    {
        return $this->futureState;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $data = [
            'deliveryExecutionId' => $this->deliveryExecutionId,
            'futureState' => $this->futureState
        ];

        if (!is_null($this->reason)){
            $data['reason'] = json_encode($this->reason);
        }

        if (!is_null($this->testCenter)){
            $data['testCenter'] = json_encode($this->reason);
        }

        return $data;
    }


}
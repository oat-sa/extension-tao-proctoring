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

namespace oat\taoProctoring\model;


use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\StateServiceInterface;

/**
 * Interface DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryExecutionStateService extends StateServiceInterface
{

    /**
     * Sets a delivery execution in the awaiting state
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function waitExecution(DeliveryExecution $deliveryExecution);

    /**
     * Sets a delivery execution in the inprogress state
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function resumeExecution(DeliveryExecution $deliveryExecution);

    /**
     * Authorises a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @param string $testCenter test center uri
     * @return bool
     */
    public function authoriseExecution(DeliveryExecution $deliveryExecution, $reason = null, $testCenter = null);

    /**
     * Pauses a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     * @throws \Exception
     */
    public function pauseExecution(DeliveryExecution $deliveryExecution, $reason = null);

    /**
     * Cancel a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function cancelExecution(DeliveryExecution $deliveryExecution, $reason = null);

    /**
     * Whether delivery execution can be canceled or not
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function isCancelable(DeliveryExecution $deliveryExecution);

    /**
     * Report irregularity to a delivery execution
     *
     * @todo remove this method to separate service
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function reportExecution(DeliveryExecution $deliveryExecution, $reason);

}

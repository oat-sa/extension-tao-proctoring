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

namespace oat\taoProctoring\model\monitorCache;

use oat\taoDelivery\model\execution\DeliveryExecution;
use qtism\runtime\tests\AssessmentTestSession;

/**
 * Interface DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringData
{
    /**
     * Set delivery execution
     * @param DeliveryExecution $deliveryExecution
     */
    public function setDeliveryExecution(DeliveryExecution $deliveryExecution);

    /**
     * Set test session
     * @param AssessmentTestSession $testSession
     */
    public function setTestSession(AssessmentTestSession $testSession);

    /**
     * Add value.
     * @param $key
     * @param $value
     * @param bool $overwrite
     */
    public function addValue($key, $value, $overwrite = false);

    /**
     * Get delivery execution data
     * @return array
     */
    public function get();

    /**
     * Validate data
     * @return boolean
     */
    public function validate();

    /**
     * Refresh delivery information in storage
     */
    public function updateDeliveryLabel();


    /**
     * Refresh testtaker information in storage
     */
    public function updateTestTakerData();


    /**
     * Refresh test session information in storage
     */
    public function updateStatus();

}
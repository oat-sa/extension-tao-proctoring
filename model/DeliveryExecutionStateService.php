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

use oat\taoDelivery\models\classes\execution\DeliveryExecution;

/**
 * Interface DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryExecutionStateService
{
    const SERVICE_ID = 'taoProctoring/DeliveryExecutionState';

    const STATE_INIT = 'INIT';
    const STATE_AWAITING = 'AWAITING';
    const STATE_AUTHORIZED = 'AUTHORIZED';
    const STATE_INPROGRESS = 'INPROGRESS';
    const STATE_PAUSED = 'PAUSED';
    const STATE_COMPLETED = 'COMPLETED';
    const STATE_TERMINATED = 'TERMINATED';

    /**
     * Computes the state of the delivery and returns one of the extended state code
     *
     * @param DeliveryExecution $deliveryExecution
     * @return null|string
     * @throws \common_Exception
     */
    public function getState(DeliveryExecution $deliveryExecution);

    /**
     * Sets a delivery execution in the awaiting state
     *
     * @param string $executionId
     * @return bool
     */
    public function waitExecution($executionId);

    /**
     * Sets a delivery execution in the inprogress state
     *
     * @param string $executionId
     * @return bool
     */
    public function resumeExecution($executionId);

    /**
     * Authorises a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @param string $testCenter test center uri
     * @return bool
     */
    public function authoriseExecution($executionId, $reason = null, $testCenter = null);

    /**
     * Terminates a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function terminateExecution($executionId, $reason = null);

    /**
     * Pauses a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function pauseExecution($executionId, $reason = null);

    /**
     * Report irregularity to a delivery execution
     *
     * @param string $executionId
     * @param array $reason
     * @return bool
     */
    public function reportExecution($executionId, $reason);

    /**
     * Sets a proctoring state on a delivery execution. Use the test state storage.
     * @param string|DeliveryExecution $executionId
     * @param string $state
     * @param array $reason
     */
    public function setProctoringState($executionId, $state, $reason = null);

    /**
     * Gets a proctoring state from a delivery execution. Use the test state storage.
     * @param string|DeliveryExecution $executionId
     * @return array
     */
    public function getProctoringState($executionId);

    /**
     * Gets the test session for a particular deliveryExecution
     *
     * @todo move this method to separate service.
     * @param DeliveryExecution $deliveryExecution
     * @return \qtism\runtime\tests\AssessmentTestSession
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function getTestSession(DeliveryExecution $deliveryExecution);

    /**
     * @todo move this method to separate service.
     * @param DeliveryExecution $deliveryExecution
     * @return array
     * Example:
     * <pre>
     * array(
     *   'QtiTestCompilation' => 'http://sample/first.rdf#i14369768868163155-|http://sample/first.rdf#i1436976886612156+',
     *   'QtiTestDefinition' => 'http://sample/first.rdf#i14369752345581135'
     * )
     * </pre>
     */
    public function getRuntimeInputParameters(DeliveryExecution $deliveryExecution);
}
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
/**
 * Interface to assign test-takers to a delivery
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
interface ProctorAssignment extends ProctorMonitor
{
    /**
     * Gets the test takers assigned to a delivery
     *
     * @param $deliveryId
     * @param string $testCenterId
     * @param array $options
     * @return User[]
     */
    public function getDeliveryTestTakers($deliveryId, $testCenterId, $options = array());

    /**
     * Gets the test takers available for a delivery
     *
     * @param User $proctor
     * @param string $deliveryId
     * @param string $testCenterId
     * @param array $options
     * @return User[]
     */
    public function getAvailableTestTakers(User $proctor, $deliveryId, $testCenterId, $options = array());

    /**
     * Assign a test taker to a delivery in the context of a test center
     *
     * @param string $testTakerId
     * @param string $deliveryId
     * @param string $testCenterId
     * @return bool
     */
    public function assignTestTaker($testTakerId, $deliveryId, $testCenterId);

    /**
     * Unassign (remove) a test taker to a delivery in the context of a test center
     *
     * @param string $testTakerId
     * @param string $deliveryId
     * * @param string $testCenterId
     * @return bool
     */
    public function unassignTestTaker($testTakerId, $deliveryId, $testCenterId);

}

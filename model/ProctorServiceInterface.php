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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model;


use oat\oatbox\user\User;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
/**
 * Interface for services that manage proctor access rights
 */
interface ProctorServiceInterface
{
    const SERVICE_ID = 'taoProctoring/ProctorAccess';

    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @param string $context
     * @return \core_kernel_classes_Resource[] deliveries
     */
    public function getProctorableDeliveries(User $proctor, $context = null);

    /**
     * Returns the data of the delivery executions the proctor is allowed to see and manager
     *
     * @param User $proctor
     * @param \core_kernel_classes_Resource $delivery
     * @param string $context
     * @param array $options
     * @return DeliveryMonitoringData[]
     */
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = []);

    /**
     * Returns the ammount of delivery ececutions a proctor is allowed to administer.
     * Uses the same filtering options as getProctorableDeliveryExecutions
     *
     * @param User $proctor
     * @param \core_kernel_classes_Resource $delivery
     * @param string $context
     * @param array $options
     * @return integer
     */
    public function countProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = []);
}

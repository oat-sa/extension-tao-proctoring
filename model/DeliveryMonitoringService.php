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

/**
 * Interface DeliveryMonitoringService
 *
 * Usage example:
 *
 * Save
 * ----
 *
 * ```php
 * $data = new DeliveryMonitoringData($deliveryExecutionInstance);
 * $data->setData([
 *  'test_taker' => 'John',
 *  'status' => 'ACTIVE',
 *  'current_item' => 'http://sample/first.rdf#i145018936535755'
 * ]);
 * $deliveryMonitoringService->save($data);
 * ```
 *
 * Find
 * ----
 *
 * ```php
 * $data = $deliveryMonitoringService->find([
 *   'state' => 'ACTIVE'
 * ],[
 *   'limit' => 10,
 *   'order' = >'id ASC',
 * ]);
 * ```
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringService
{
    /**
     * @param array $criteria
     * @param array $options
     * @return DeliveryMonitoring[]
     */
    public function find(array $criteria, array $options);

    /**
     * @param DeliveryMonitoring $deliveryMonitoring
     * @return mixed
     */
    public function save(DeliveryMonitoring $deliveryMonitoring);

    /**
     * @param DeliveryMonitoring $deliveryMonitoring
     * @return mixed
     */
    public function delete(DeliveryMonitoring $deliveryMonitoring);
}
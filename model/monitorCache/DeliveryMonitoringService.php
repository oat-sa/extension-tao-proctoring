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

/**
 * Interface DeliveryMonitoringService
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringService
{

    const CONFIG_ID = 'taoProctoring/DeliveryMonitoring';

    /**
     * @param array $criteria
     * @param array $options
     * @return DeliveryMonitoringData[]
     */
    public function find(array $criteria, array $options);

    /**
     * @param DeliveryMonitoringData $deliveryMonitoring
     * @return mixed
     */
    public function save(DeliveryMonitoringData $deliveryMonitoring);

    /**
     * @param DeliveryMonitoringData $deliveryMonitoring
     * @return mixed
     */
    public function delete(DeliveryMonitoringData $deliveryMonitoring);
}
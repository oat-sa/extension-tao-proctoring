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

/**
 * Interface DeliveryMonitoringService
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringService
{
    const CONFIG_ID = 'taoProctoring/DeliveryMonitoring';

    const ID = 'id';
    const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const STATUS = 'status';
    const CURRENT_ASSESSMENT_ITEM = 'current_assessment_item';
    const TEST_TAKER = 'test_taker';
    const AUTHORIZED_BY = 'authorized_by';
    const START_TIME = 'start_time';
    const END_TIME = 'end_time';


    const TEST_TAKER_FIRST_NAME = 'test_taker_first_name';
    const TEST_TAKER_LAST_NAME = 'test_taker_last_name';
    const TEST_CENTER_ID = 'test_center_id';
    const DELIVERY_ID = 'delivery_id';
    const DELIVERY_NAME = 'delivery_name';
    const CONNECTIVITY = 'last_connect';

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return DeliveryMonitoringData
     */
    public function getData(DeliveryExecution $deliveryExecution);

    /**
     * @return DeliveryMonitoringData[]
     */
    public function find();

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
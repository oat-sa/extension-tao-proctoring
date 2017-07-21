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

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;

/**
 * Interface DeliveryMonitoringService
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringService
{
    /**
     * @deprecated
     */
    const CONFIG_ID = 'taoProctoring/DeliveryMonitoring';

    /**
     * Service Id of the main Monitoring Cache Service
     * @var string
     */
    const SERVICE_ID = 'taoProctoring/DeliveryMonitoring';

    const ID = 'id';
    const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const STATUS = 'status';
    const CURRENT_ASSESSMENT_ITEM = 'current_assessment_item';
    const TEST_TAKER = 'test_taker';
    const AUTHORIZED_BY = 'authorized_by';
    const START_TIME = 'start_time';
    const END_TIME = 'end_time';
    const REMAINING_TIME = 'remaining_time';
    const EXTRA_TIME = 'extra_time';
    const EXTENDED_TIME = 'extended_time';
    const CONSUMED_EXTRA_TIME = 'consumed_extra_time';
    const ALLOW_EXTRA_TIME = 'allow_extra_time';

    const TEST_TAKER_FIRST_NAME = 'test_taker_first_name';
    const TEST_TAKER_LAST_NAME = 'test_taker_last_name';
    const DELIVERY_ID = 'delivery_id';
    const DELIVERY_NAME = 'delivery_name';
    const CONNECTIVITY = 'last_connect';

    /**
     * Retrieve the currently cached delivery data
     * 
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return DeliveryMonitoringData
     */
    public function getData(DeliveryExecutionInterface $deliveryExecution);

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

    /**
     * @return integer
     */
    public function count();
}

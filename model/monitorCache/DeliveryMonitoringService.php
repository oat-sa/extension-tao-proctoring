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
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDelete;

/**
 * Interface DeliveryMonitoringService
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryMonitoringService extends DeliveryExecutionDelete
{
    /**
     * @deprecated
     */
    public const CONFIG_ID = 'taoProctoring/DeliveryMonitoring';

    /**
     * Service Id of the main Monitoring Cache Service
     * @var string
     */
    public const SERVICE_ID = 'taoProctoring/DeliveryMonitoring';

    public const ID = 'id';
    public const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    public const STATUS = 'status';
    public const CURRENT_ASSESSMENT_ITEM = 'current_assessment_item';
    public const TEST_TAKER = 'test_taker';
    public const AUTHORIZED_BY = 'authorized_by';
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const REMAINING_TIME = 'remaining_time';
    public const EXTRA_TIME = 'extra_time';
    public const EXTENDED_TIME = 'extended_time';
    public const CONSUMED_EXTRA_TIME = 'consumed_extra_time';
    public const ALLOW_EXTRA_TIME = 'allow_extra_time';

    public const LAST_TEST_TAKER_ACTIVITY = 'last_test_taker_activity';
    public const LAST_TEST_STATE_CHANGE = 'last_test_state_change';
    public const LAST_PAUSE_TIMESTAMP = 'last_pause_timestamp';

    public const DIFF_TIMESTAMP = 'diff_timestamp';
    public const ITEM_DURATION = 'item_duration';
    public const STORED_ITEM_DURATION = 'stored_item_duration';

    public const TEST_TAKER_FIRST_NAME = 'test_taker_first_name';
    public const TEST_TAKER_LAST_NAME = 'test_taker_last_name';
    public const DELIVERY_ID = 'delivery_id';
    public const DELIVERY_NAME = 'delivery_name';
    public const CONNECTIVITY = 'last_connect';

    public const REACTIVATE_AUTHORIZED_BY = 'reactivate_authorized_by';

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $data
     * @return DeliveryMonitoringData
     */
    public function createMonitoringData(DeliveryExecutionInterface $deliveryExecution, $data);

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
    public function partialSave(DeliveryMonitoringData $deliveryMonitoring);

    /**
     * @param DeliveryMonitoringData $deliveryMonitoring
     * @return mixed
     */
    public function delete(DeliveryMonitoringData $deliveryMonitoring);

    /**
     * @return integer
     */
    public function count();

    /**
     * Get statistic by statuses grouped by deliveries.
     * Result is an array of deliveries with amount of delivery executions in each status
     */
    public function getStatusesStatistic($limit = 0, $offset = 0, $orderby = 'delivery_name', $orderdir = 'asc');

    /**
     * Count statistic by statuses grouped by deliveries.
     */
    public function getCountOfStatistics();
}

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

namespace oat\taoProctoring\model\monitorCache\implementation;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService as DeliveryMonitoringServiceInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use \oat\oatbox\service\ConfigurableService;

/**
 * Class DeliveryMonitoringService
 *
 * Usage example:
 *
 * Save
 * ----
 *
 * ```php
 * $data = new DeliveryMonitoringData($deliveryExecutionId);
 * $data->setData([
 *  'test_taker' => 'http://sample/first.rdf#i1450190828500474',
 *  'status' => 'ACTIVE',
 *  'current_assessment_item' => 'http://sample/first.rdf#i145018936535755'
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
class DeliveryMonitoringService extends ConfigurableService implements DeliveryMonitoringServiceInterface
{

    const TABLE_NAME = 'delivery_monitoring';
    const COLUMN_ID = 'id';
    const COLUMN_DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const COLUMN_STATUS = 'status';
    const COLUMN_CURRENT_ASSESSMENT_ITEM = 'current_assessment_item';
    const COLUMN_TEST_TAKER = 'test_taker';
    const COLUMN_AUTHORIZED_BY = 'authorized_by';
    const COLUMN_START_TIME = 'start_time';
    const COLUMN_END_TIME = 'end_time';

    const KV_TABLE_NAME = 'kv_delivery_monitoring';
    const KV_COLUMN_ID = 'id';
    const KV_COLUMN_PARENT_ID = 'parent_id';
    const KV_COLUMN_KEY = 'monitoring_key';
    const KV_COLUMN_VALUE = 'monitoring_value';
    const KV_FK_PARENT = 'FK_DeliveryMonitoring_kvDeliveryMonitoring';

    /**
     * @param array $criteria
     * @param array $options
     * @return DeliveryMonitoringData[]
     */
    public function find(array $criteria, array $options)
    {

    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return mixed
     */
    public function save(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {

    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return mixed
     */
    public function delete(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {

    }
}
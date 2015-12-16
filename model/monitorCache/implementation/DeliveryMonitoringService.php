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

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringServiceInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringDataInterface;
use oat\oatbox\service\ConfigurableService;

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

    private $persistence;

    /**
     * @var array
     */
    private $primaryTableColumns;

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
     * @return boolean whether data is saved
     */
    public function save(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $result = false;
        if ($deliveryMonitoring->validate()) {
            $data = $deliveryMonitoring->get();

            $primaryTableData = $this->extractPrimaryData($data);
            $kvTableData = $this->extractKvData($data);

            $this->getPersistence()->insert(self::TABLE_NAME, $primaryTableData);
            $id = $this->persistence->lastInsertId(self::TABLE_NAME);

            foreach($kvTableData as $kvDataKey => $kvDataValue) {
                $this->getPersistence()->insert(
                    self::KV_TABLE_NAME,
                    array(
                        self::KV_COLUMN_PARENT_ID => $id,
                        self::KV_COLUMN_KEY => $kvDataKey,
                        self::KV_COLUMN_VALUE => $kvDataValue,
                    )
                );
            }
            $result = true;
        }
        return $result;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoringData
     * @return boolean
     */
    public function delete(DeliveryMonitoringDataInterface $deliveryMonitoringData)
    {
        $data = $deliveryMonitoringData->get();

        $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                WHERE ' . self::COLUMN_DELIVERY_EXECUTION_ID . '=?';

        return $this->getPersistence()->exec($sql, [$data[self::COLUMN_DELIVERY_EXECUTION_ID]]) === 1;
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    private function getPersistence()
    {
        if ($this->persistence === null) {
            $this->persistence = \common_persistence_Manager::getPersistence('default');
        }
        return $this->persistence;
    }

    /**
     * Get list of table column names
     * @return array
     */
    private function getPrimaryColumns()
    {
        if ($this->primaryTableColumns === null) {
            $schemaManager = $this->getPersistence()->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $this->primaryTableColumns = array_keys($schema->getTable(self::TABLE_NAME)->getColumns());
        }
        return $this->primaryTableColumns;
    }

    /**
     * @param array $data
     * @return array
     */
    private function extractPrimaryData(array $data)
    {
        $result = [];
        $primaryTableCols = $this->getPrimaryColumns();
        foreach ($primaryTableCols as $primaryTableCol) {
            if (isset($data[$primaryTableCol])) {
                $result[$primaryTableCol] = $data[$primaryTableCol];
            }
        }
        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    private function extractKvData(array $data)
    {
        $result = [];
        $primaryTableCols = $this->getPrimaryColumns();
        foreach ($data as $key => $val) {
            if (!in_array($key, $primaryTableCols)) {
                $result[$key] = $val;
            }
        }
        return $result;
    }
}
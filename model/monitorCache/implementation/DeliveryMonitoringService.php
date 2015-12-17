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
     * [
     *   ['error_code' => '1'],
     *   'OR',
     *   ['error_code' => '2'],
     * ]
     * @param array $options
     * @return DeliveryMonitoringData[]
     */
    public function find(array $criteria = [], array $options = [])
    {
        $defaultOptions = [
            'order' => self::COLUMN_ID." ASC",
            'offset' => 0,
        ];
        $options = array_merge($defaultOptions, $options);

        $whereClause = 'WHERE ';
        $parameters = [];

        $whereClause .= $this->prepareCondition($criteria, $parameters);

        $sql = "SELECT DISTINCT t.* FROM " . self::TABLE_NAME . " t
                INNER JOIN " . self::KV_TABLE_NAME . " kv_t ON kv_t. " . self::KV_COLUMN_PARENT_ID . " = t." . self::COLUMN_ID . "
                $whereClause
                ORDER BY " . $options['order'];

        if (isset($options['limit']))  {
            $sql = $this->getPersistence()->getPlatForm()->limitStatement($sql, $options['limit'], $options['offset']);
        }

        $stmt = $this->getPersistence()->query($sql, $parameters);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public function prepareCondition($condition, &$parameters)
    {
        $whereClause = '';

        if (is_string($condition) && in_array(mb_strtoupper($condition), ['OR', 'AND'])) {
            $whereClause .= " $condition ";
        } else if (is_array($condition) && count($condition) > 1) {
            $whereClause .=  '(';
            foreach ($condition as $subCondition) {
                $whereClause .=  $this->prepareCondition($subCondition, $parameters);
            }
            $whereClause .=  ')';
        } else if (is_array($condition) && count($condition) === 1){
            $primaryColumns = $this->getPrimaryColumns();
            $key = array_keys($condition)[0];
            $value = $condition[$key];

            if ($value === null) {
                $op = 'IS NULL';
            } else if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|LIKE|NOT\sLIKE))?(.*)$/', $value, $matches)) {
                $value = $matches[2];
                $op = $matches[1] ? $matches[1] : "=";
                $op .= ' ?';
            }

            if (in_array($key, $primaryColumns)) {
                $whereClause .= " t.$key $op ";
            } else {
                $whereClause .= " (kv_t.monitoring_key = ? AND kv_t.monitoring_value $op) ";
                $parameters[] = trim($key);
            }

            if ($value !== null) {
                $parameters[] = trim($value);
            }
        }
        return $whereClause;
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
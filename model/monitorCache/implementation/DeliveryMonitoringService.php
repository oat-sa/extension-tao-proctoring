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

use core_kernel_classes_Resource;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService as DeliveryMonitoringServiceInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;

/**
 * Class DeliveryMonitoringService
 *
 * Usage example:
 *
 * Save
 * ----
 *
 * ```php
 * $data = new DeliveryMonitoringData($deliveryExecution);
 * $data->addValue('new_key', 'new_value');
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
    const OPTION_PERSISTENCE = 'persistence';

    const TABLE_NAME = 'delivery_monitoring';

    const COLUMN_ID = 'id';
    const COLUMN_DELIVERY_EXECUTION_ID = DeliveryMonitoringServiceInterface::DELIVERY_EXECUTION_ID;
    const COLUMN_DELIVERY_TEST_CENTER_ID = DeliveryMonitoringServiceInterface::TEST_CENTER_ID;
    const COLUMN_STATUS = DeliveryMonitoringServiceInterface::STATUS;
    const COLUMN_CURRENT_ASSESSMENT_ITEM = DeliveryMonitoringServiceInterface::CURRENT_ASSESSMENT_ITEM;
    const COLUMN_TEST_TAKER = DeliveryMonitoringServiceInterface::TEST_TAKER;
    const COLUMN_TEST_TAKER_FIRST_NAME = DeliveryMonitoringServiceInterface::TEST_TAKER_FIRST_NAME;
    const COLUMN_TEST_TAKER_LAST_NAME = DeliveryMonitoringServiceInterface::TEST_TAKER_LAST_NAME;
    const COLUMN_AUTHORIZED_BY = DeliveryMonitoringServiceInterface::AUTHORIZED_BY;
    const COLUMN_START_TIME = DeliveryMonitoringServiceInterface::START_TIME;
    const COLUMN_END_TIME = DeliveryMonitoringServiceInterface::END_TIME;

    const KV_TABLE_NAME = 'kv_delivery_monitoring';
    const KV_COLUMN_ID = 'id';
    const KV_COLUMN_PARENT_ID = 'parent_id';
    const KV_COLUMN_KEY = 'monitoring_key';
    const KV_COLUMN_VALUE = 'monitoring_value';
    const KV_FK_PARENT = 'FK_DeliveryMonitoring_kvDeliveryMonitoring';


    const DEFAULT_SORT_COLUMN = self::COLUMN_START_TIME;
    const DEFAULT_SORT_ORDER = 'DESC';

    protected $joins = [];

    /**
     * @var array
     */
    protected $primaryTableColumns;

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param boolean $updateData whether DeliveryMonitoringData instance should be populated by data during instantiation.
     * @return DeliveryMonitoringDataInterface
     */
    public function getData(DeliveryExecution $deliveryExecution, $updateData = true)
    {
        return new DeliveryMonitoringData($deliveryExecution, $updateData);
    }

    /**
     * Find delivery monitoring data.
     *
     * Examples:
     * Find by delivery execution id:
     * ------------------------------
     * ```php
     * $deliveryMonitoringService->find([
     *     ['delivery_execution_id' => 'http://sample/first.rdf#i1450191587554175']
     * ]);
     * ```
     *
     * Find by two fields with `AND` operator
     * --------------------------------------
     * ```php
     * $deliveryMonitoringService->find([
     *     ['status' => 'active'],
     *     ['start_time' => '>1450428401'],
     * ]);
     * ```
     *
     * Find by two fields with `OR` operator
     * -------------------------------------
     * ```php
     * $deliveryMonitoringService->find([
     *     ['status' => 'active'],
     *     'OR',
     *     ['start_time' => '>1450428401'],
     * ]);
     * ```
     *
     *
     * Combined condition
     * ------------------
     * ```php
     * $deliveryMonitoringService->find([
     *    ['status' => 'finished'],
     *    'AND',
     *    [['error_code' => '0'], 'OR', ['error_code' => '1']],
     * ]);
     * ```
     * @param array $criteria - criteria to find data.
     * The comparison operator is determined based on the first few
     * characters in the given value. It recognizes the following operators
     * if they appear as the leading characters in the given value:
     * <ul>
     *   <li><code>&lt;</code>: the column must be less than the given value.</li>
     *   <li><code>&gt;</code>: the column must be greater than the given value.</li>
     *   <li><code>&lt;=</code>: the column must be less than or equal to the given value.</li>
     *   <li><code>&gt;=</code>: the column must be greater than or equal to the given value.</li>
     *   <li><code>&lt;&gt;</code>: the column must not be the same as the given value.</li>
     *   <li><code>=</code>: the column must be equal to the given value.</li>
     *   <li>none of the above: the column must be equal to the given value.</li>
     * </ul>
     * @param array $options
     * <ul>
     *   <li>string `$options['order']='id ASC'`</li>
     *   <li>integer `$options['limit']=null`</li>
     *   <li>integer `$options['offset']=0`</li>
     *   <li>integer `$options['asArray']=false` whether data should be returned as multidimensional or as array of `DeliveryMonitoringData` instances</li>
     * </ul>
     * @param boolean $together - whether the secondary data should be fetched together with primary.
     * @return DeliveryMonitoringData[]
     */
    public function find(array $criteria = [], array $options = [], $together = false)
    {
        $result = [];
        $defaultOptions = [
            'order' => static::COLUMN_ID." ASC",
            'offset' => 0,
            'asArray' => false
        ];
        $options = array_merge($defaultOptions, $options);

        $whereClause = 'WHERE ';
        $parameters = [];

        $options['order'] = $this->prepareOrderStmt($options['order']);
        $selectClause = "SELECT DISTINCT t.* ";
        $fromClause = "FROM " . self::TABLE_NAME . " t ";
        $whereClause .= $this->prepareCondition($criteria, $parameters, $selectClause);

        $sql = $selectClause . $fromClause . PHP_EOL .
            implode(PHP_EOL, $this->joins) . PHP_EOL .
            $whereClause . PHP_EOL;

        if ($options['order']['primary']) {
            $sql .= "ORDER BY " . $options['order']['primary'];
        }

        if (isset($options['limit']))  {
            $sql = $this->getPersistence()->getPlatForm()->limitStatement($sql, $options['limit'], $options['offset']);
        }

        $stmt = $this->getPersistence()->query($sql, $parameters);

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($together) {
            foreach ($data as &$row) {
                $row = array_merge($row, $this->getKvData($row[static::COLUMN_ID]));
            }
            unset($row);

            if ($data) {
                $data = $this->orderResult($data, $options['order']);
            }
        }

        if ($options['asArray']) {
            $result = $data;
        } else {
            foreach($data as $row) {
                $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($row[self::COLUMN_DELIVERY_EXECUTION_ID]);
                $monitoringData = new DeliveryMonitoringData($deliveryExecution, false);
                $result[] = $monitoringData;
            }
        }

        return $result;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     */
    public function save(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $result = false;
        if ($deliveryMonitoring->validate()) {
            $isNewRecord = $this->isNewRecord($deliveryMonitoring);

            if ($isNewRecord) {
                $result = $this->create($deliveryMonitoring);
            } else {
                $result = $this->update($deliveryMonitoring);
            }
        }
        return $result;
    }

    /**
     * Create new record
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     */
    protected function create(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();

        $primaryTableData = $this->extractPrimaryData($data);

        $result = $this->getPersistence()->insert(self::TABLE_NAME, $primaryTableData) === 1;

        if ($result) {
            $id = $this->getPersistence()->lastInsertId(self::TABLE_NAME);

            $data[static::COLUMN_ID] = $id;
            $deliveryMonitoring->addValue(static::COLUMN_ID, $id);
            $this->saveKvData($deliveryMonitoring);
        }

        return $result;
    }

    /**
     * Update existing record
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     */
    protected function update(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $setClause = '';
        $params = [':delivery_execution_id' => $deliveryMonitoring->get()[self::COLUMN_DELIVERY_EXECUTION_ID]];

        $data = $deliveryMonitoring->get();
        $primaryTableData = $this->extractPrimaryData($data);

        unset($primaryTableData['delivery_execution_id']);
        foreach ($primaryTableData as $dataKey => $dataValue) {
            $setClause .= ($setClause === '') ? "$dataKey = :$dataKey" : ", $dataKey = :$dataKey";
            $params[":$dataKey"] = $dataValue;
        }

        $sql = "UPDATE " . self::TABLE_NAME . " SET $setClause
        WHERE " . self::COLUMN_DELIVERY_EXECUTION_ID . '=:delivery_execution_id';

        $this->getPersistence()->exec($sql, $params);

        $this->saveKvData($deliveryMonitoring);

        return true;
    }

    /**
     * Delete all related records from secondary table
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     */
    protected function saveKvData(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();
        $isNewRecord = $this->isNewRecord($deliveryMonitoring);
        $kvTableData = $this->extractKvData($data);

        if (!$isNewRecord && !empty($kvTableData)) {
            $this->deleteKvData($deliveryMonitoring);
            $id = $data[static::COLUMN_ID];
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
        }
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    public function delete(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();

        $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                WHERE ' . self::COLUMN_DELIVERY_EXECUTION_ID . '=?';

        return $this->getPersistence()->exec($sql, [$data[self::COLUMN_DELIVERY_EXECUTION_ID]]) === 1;
    }


    /**
     * Delete all related records from secondary table
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    protected function deleteKvData(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $result = false;
        $data = $deliveryMonitoring->get();
        $isNewRecord = $this->isNewRecord($deliveryMonitoring);

        if (!$isNewRecord) {
            $sql = 'DELETE FROM ' . self::KV_TABLE_NAME . '
                    WHERE ' . self::KV_COLUMN_PARENT_ID . '=?';
            $this->getPersistence()->exec($sql, [$data[static::COLUMN_ID]]);
            $result = true;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param $order
     * @return array
     */
    protected function orderResult(array $data, array $order)
    {
        if (empty($order['kv'])) {
            return $data;
        }
        $sortingData = [];
        foreach ($order['kv'] as $orderRule) {
            foreach ($data as $key => $row) {
                $sortingData[$orderRule[0]][$key] = isset($row[$orderRule[0]]) ? $row[$orderRule[0]] : null;
            }
        }

        $args = [];

        foreach ($order['kv'] as $orderRule) {
            $args[] = $sortingData[$orderRule[0]];
            $args[] = isset($orderRule[1]) && strcasecmp($orderRule[1], 'desc') === 0 ? SORT_DESC : SORT_ASC;
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
     * @param $order
     * @return array
     */
    protected function prepareOrderStmt($order)
    {
        $order = explode(',', $order);
        $result = [
            'primary' => [],
            'kv' => [],
        ];
        $primaryTableColumns = $this->getPrimaryColumns();
        foreach ($order as $ruleNum => $orderRule) {
            preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s?(asc|desc)?/i', $orderRule, $ruleParts);
            if (in_array($ruleParts[1], $primaryTableColumns)) {
                $result['primary'][] = $orderRule;
            } else {
                array_shift($ruleParts);
                $result['kv'][] = $ruleParts;
            }
        }

        $result['primary'] = implode(', ', $result['primary']);

        return $result;
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        return \common_persistence_Manager::getPersistence($this->getOption(self::OPTION_PERSISTENCE));
    }

    /**
     * Get list of table column names
     * @return array
     */
    protected function getPrimaryColumns()
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
    protected function extractPrimaryData(array $data)
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
    protected function extractKvData(array $data)
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

    /**
     * Get secondary data by parent data id
     * @param integer $id
     * @return array
     */
    protected function getKvData($id)
    {
        $result = [];
        $sql = 'SELECT * FROM ' . self::KV_TABLE_NAME . '
                WHERE ' . self::KV_COLUMN_PARENT_ID . '=?';
        $secondaryData = $this->getPersistence()->query($sql, [$id])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($secondaryData as $data) {
            $result[$data[self::KV_COLUMN_KEY]] = $data[self::KV_COLUMN_VALUE];
        }

        return $result;
    }

    /**
     * @param $condition
     * @param $parameters
     * @param $selectClause
     * @return string
     */
    protected function prepareCondition($condition, &$parameters, &$selectClause)
    {
        $whereClause = '';

        if (is_array($condition) && count($condition) === 1 && is_array(current($condition))) {
            $condition = current($condition);
        }

        if (is_string($condition) && in_array(mb_strtoupper($condition), ['OR', 'AND'])) {
            $whereClause .= " $condition ";
        } else if (is_array($condition) && count($condition) > 1) {
            $whereClause .=  '(';
            $previousCondition = null;
            foreach ($condition as $subCondition) {
                if (is_array($subCondition) && is_array($previousCondition)) {
                    $whereClause .= 'AND';
                }
                $whereClause .=  $this->prepareCondition($subCondition, $parameters, $selectClause);
                $previousCondition = $subCondition;
            }
            $whereClause .=  ')';
        } else if (is_array($condition) && count($condition) === 1) {
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
                $joinNum = count($this->joins);
                $whereClause .= " (kv_t_$joinNum.monitoring_key = ? AND kv_t_$joinNum.monitoring_value $op) ";
                $this->joins[] = "LEFT JOIN " . self::KV_TABLE_NAME . " kv_t_$joinNum ON kv_t_$joinNum. " . self::KV_COLUMN_PARENT_ID . " = t." . static::COLUMN_ID . PHP_EOL;
                $parameters[] = trim($key);
            }

            if ($value !== null) {
                $parameters[] = trim($value);
            }
        }
        return $whereClause;
    }

    /**
     * Check if record for delivery execution already exists in the storage.
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    protected function isNewRecord(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();
        $deliveryExecutionId = $data[self::COLUMN_DELIVERY_EXECUTION_ID];

        if (isset($data[static::COLUMN_ID])) {
            $exists = true;
        } else {
            $sql = "SELECT EXISTS( " . PHP_EOL .
                "SELECT " . self::COLUMN_DELIVERY_EXECUTION_ID . PHP_EOL .
                "FROM " . self::TABLE_NAME . PHP_EOL .
                "WHERE " . self::COLUMN_DELIVERY_EXECUTION_ID . "=?)";
            $exists = $this->getPersistence()->query($sql, [$deliveryExecutionId])->fetch(\PDO::FETCH_COLUMN);
        }

        return !((boolean) $exists);
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $testCenter
     * @param array $options
     * @return DeliveryMonitoringData[]
     */
    public function getCurrentDeliveryExecutions(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $testCenter, array $options = array())
    {

        $sortBy = $this->getSortByColumn(array_key_exists('sortBy',$options )?$options['sortBy']:'');
        $sortOrder = array_key_exists('sortOrder', $options) ? $options['sortOrder'] : self::DEFAULT_SORT_ORDER;

        $result = $this->find([
            [self::TEST_CENTER_ID => $testCenter->getUri()],
            'AND',
            [self::DELIVERY_ID => $delivery->getUri()]
        ], ['asArray' => true,
            'order'=> $sortBy.' '. $sortOrder,
        ],
            true);

        return $result;
    }

    /**
     * @param string $sortBy
     * @return string
     */
    private function getSortByColumn($sortBy)
    {
        $map = array_merge([
            'firstname' => self::COLUMN_TEST_TAKER_FIRST_NAME,
            'lastname' => self::TEST_TAKER_LAST_NAME,
            'delivery' => self::DELIVERY_NAME,
            'status' => self::STATUS,
            'connectivity' => self::CONNECTIVITY,
        ],
            array_combine(array_map(function ($property) {
                return strtolower($property['id']);
            }, DeliveryHelper::getExtraFields()), array_map(function ($property) {
                return $property['id'];
            }, DeliveryHelper::getExtraFields())));

        return array_key_exists(strtolower($sortBy), $map) ? $map[strtolower($sortBy)] : self::DEFAULT_SORT_COLUMN;
    }
}
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

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
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
class MonitoringStorage extends ConfigurableService implements DeliveryMonitoringService
{
    const OPTION_PERSISTENCE = 'persistence';

    const OPTION_PRIMARY_COLUMNS = 'primary_columns';

    const TABLE_NAME = 'delivery_monitoring';

    const COLUMN_ID = DeliveryMonitoringService::DELIVERY_EXECUTION_ID;
    const COLUMN_DELIVERY_EXECUTION_ID = DeliveryMonitoringService::DELIVERY_EXECUTION_ID;
    const COLUMN_STATUS = DeliveryMonitoringService::STATUS;
    const COLUMN_CURRENT_ASSESSMENT_ITEM = DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM;
    const COLUMN_TEST_TAKER = DeliveryMonitoringService::TEST_TAKER;
    const COLUMN_TEST_TAKER_FIRST_NAME = DeliveryMonitoringService::TEST_TAKER_FIRST_NAME;
    const COLUMN_TEST_TAKER_LAST_NAME = DeliveryMonitoringService::TEST_TAKER_LAST_NAME;
    const COLUMN_AUTHORIZED_BY = DeliveryMonitoringService::AUTHORIZED_BY;
    const COLUMN_START_TIME = DeliveryMonitoringService::START_TIME;
    const COLUMN_END_TIME = DeliveryMonitoringService::END_TIME;
    const COLUMN_REMAINING_TIME = DeliveryMonitoringService::REMAINING_TIME;
    const COLUMN_EXTRA_TIME = DeliveryMonitoringService::EXTRA_TIME;
    const COLUMN_CONSUMED_EXTRA_TIME = DeliveryMonitoringService::CONSUMED_EXTRA_TIME;
    
    const KV_TABLE_NAME = 'kv_delivery_monitoring';
    const KV_COLUMN_ID = 'id';
    const KV_COLUMN_PARENT_ID = 'parent_id';
    const KV_COLUMN_KEY = 'monitoring_key';
    const KV_COLUMN_VALUE = 'monitoring_value';
    const KV_FK_PARENT = 'FK_DeliveryMonitoring_kvDeliveryMonitoring';


    const DEFAULT_SORT_COLUMN = self::COLUMN_ID;
    const DEFAULT_SORT_ORDER = 'ASC';
    const DEFAULT_SORT_TYPE = 'string';

    protected $joins = [];
    protected $queryParams = [];
    protected $selectColumns = [];
    protected $groupColumns = [];

    /**
     * @var DeliveryMonitoringData[]
     */
    protected $data = [];

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringService::getData()
     */
    public function getData(DeliveryExecutionInterface $deliveryExecution)
    {
        $id = $deliveryExecution->getIdentifier();
        if (!isset($this->data[$id])) {
            $results = $this->find([
                [self::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()],
            ], ['asArray' => true], true);
            $data = empty($results) ? [] : $results[0];
            $dataObject = new DeliveryMonitoringData($deliveryExecution, $data);
            $this->getServiceManager()->propagate($dataObject);
            $this->data[$id] = $dataObject;
        }
        return $this->data[$id];
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
     * supports also the following syntax
     * ```php
     * $deliveryMonitoringService->find([
     *    ['status' => 'finished'],
     *    'AND',
     *    [['error_code' => ['0', '1']],
     * ]);
     * ```
     * 
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
     *   <li>string `$options['order']='id ASC numeric'`</li>
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
        $this->joins = [];
        $this->queryParams = [];
        $this->selectColumns = ['t.*'];
        $this->groupColumns = ['t.delivery_execution_id'];
        $defaultOptions = [
            'order' => join(' ', [static::DEFAULT_SORT_COLUMN, static::DEFAULT_SORT_ORDER, static::DEFAULT_SORT_TYPE]),
            'offset' => 0,
            'asArray' => false
        ];
        $options = array_merge($defaultOptions, $options);

        $options['order'] = $this->prepareOrderStmt($options['order']);
        $fromClause = "FROM " . self::TABLE_NAME . " t ";

        $whereClause = $this->prepareCondition($criteria, $this->queryParams, $selectClause);
        if ($whereClause !== '') {
            $whereClause = 'WHERE ' . $whereClause;
        }
        $selectClause = "SELECT " . implode(',', $this->selectColumns);
        $sql = $selectClause . ' ' . $fromClause . PHP_EOL .
            implode(PHP_EOL, $this->joins) . PHP_EOL .
            $whereClause . PHP_EOL .
            'GROUP BY ' . implode(',', $this->groupColumns) . PHP_EOL;

        $sql .= "ORDER BY " . $options['order'];

        if (isset($options['limit']))  {
            $sql = $this->getPersistence()->getPlatForm()->limitStatement($sql, $options['limit'], $options['offset']);
        }

        $stmt = $this->getPersistence()->query($sql, $this->queryParams);

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($together) {
            $ids = array_column($data, static::COLUMN_ID);
            $kvData = $this->getKvData($ids);
            foreach ($data as &$row) {
                $row = array_merge($row, $kvData[$row[static::COLUMN_ID]]);
            }
            unset($row);
        }

        if ($options['asArray']) {
            $result = $data;
        } else {
            foreach($data as $row) {
                $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($row[self::COLUMN_DELIVERY_EXECUTION_ID]);
                $result[] = $this->getData($deliveryExecution);
            }
        }

        return $result;
    }

    /**
     * @param array $criteria
     * @return mixed
     */
    public function count(array $criteria = [])
    {
        $this->joins = [];
        $this->queryParams = [];

        $selectClause = "select COUNT(*) FROM (SELECT t.delivery_execution_id ";
        $fromClause = "FROM " . self::TABLE_NAME . " t ";
        $whereClause = $this->prepareCondition($criteria, $this->queryParams, $selectClause);
        if ($whereClause !== '') {
            $whereClause = 'WHERE ' . $whereClause;
        }
        $sql = $selectClause . $fromClause . PHP_EOL .
            implode(PHP_EOL, $this->joins) . PHP_EOL .
            $whereClause . PHP_EOL .
            'GROUP BY t.' . self::DELIVERY_EXECUTION_ID . ') as count_q';

        $stmt = $this->getPersistence()->query($sql, $this->queryParams);
        $result = $stmt->fetch(\PDO::FETCH_BOTH);
        return intval($result[0]);
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

        if (!$isNewRecord) {
            $id = $data[self::COLUMN_DELIVERY_EXECUTION_ID];
            $kvTableData = $this->extractKvData($data);

            if (empty($kvTableData)) {
                return;
            }

            $query = 'SELECT ' . self::KV_COLUMN_KEY . ',' . self::KV_COLUMN_VALUE . '
            FROM ' . self::KV_TABLE_NAME . '
            WHERE ' . self::KV_COLUMN_PARENT_ID . ' =? AND ' . self::KV_COLUMN_KEY . ' IN(';
            $keys = array_fill(0, count($kvTableData), '?');
            $query .= implode(',', $keys);
            $query .= ')';

            $params = array_merge([$id], array_keys($kvTableData));

            $stmt = $this->getPersistence()->query($query, $params);
            $existent = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $existent = array_combine(array_column($existent, self::KV_COLUMN_KEY), array_column($existent, self::KV_COLUMN_VALUE));

            foreach($kvTableData as $kvDataKey => $kvDataValue) {
                if (isset($existent[$kvDataKey]) && $existent[$kvDataKey] === $kvDataValue) {
                    continue;
                }

                if (array_key_exists($kvDataKey, $existent)) {
                    $this->getPersistence()->exec(
                        'UPDATE ' . self::KV_TABLE_NAME . '
                          SET '  . self::KV_COLUMN_VALUE . ' = ?
                        WHERE ' . self::KV_COLUMN_PARENT_ID . ' = ?
                          AND ' . self::KV_COLUMN_KEY . ' = ?;',
                        [$kvDataValue, $id, $kvDataKey]
                    );
                } else {
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
            $this->getPersistence()->exec($sql, [$data[self::COLUMN_DELIVERY_EXECUTION_ID]]);
            $result = true;
        }

        return $result;
    }

    /**
     * @param $order
     * @return array
     */
    protected function prepareOrderStmt($order)
    {
        $order = explode(',', $order);
        $result = [];
        $primaryTableColumns = $this->getPrimaryColumns();
        foreach ($order as $ruleNum => $orderRule) {
            preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s?(asc|desc)?\s?(string|numeric)?/i', $orderRule, $ruleParts);

            if (!in_array($ruleParts[1], $primaryTableColumns)) {
                $colName = $ruleParts[1];
                $joinNum = count($this->joins);
                $this->joins[] = "LEFT JOIN " . self::KV_TABLE_NAME . " kv_t_$joinNum 
                                  ON kv_t_$joinNum." . self::KV_COLUMN_PARENT_ID . " = t." . self::COLUMN_DELIVERY_EXECUTION_ID . "
                                  AND kv_t_$joinNum.monitoring_key = ?";
                $this->queryParams[] = $colName;
                $this->selectColumns[] = "kv_t_$joinNum.monitoring_value as $colName";
                $this->groupColumns[] = "kv_t_$joinNum.monitoring_value";

                $sortingColumn = "kv_t_$joinNum.monitoring_value";
            } else {
                $sortingColumn = $ruleParts[1];
            }

            $result[] = isset($ruleParts[3]) && $ruleParts[3] === 'numeric'
                ? sprintf("cast(nullif(%s, '') as decimal) %s", $sortingColumn, $ruleParts[2])
                : sprintf('%s %s', $sortingColumn, isset($ruleParts[2]) ? $ruleParts[2] : 'ASC');
        }

        $result = implode(', ', $result);

        return $result;
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        return $this->getServiceManager()
            ->get(\common_persistence_Manager::SERVICE_ID)
            ->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }

    /**
     * Get list of table column names
     * @return array
     */
    protected function getPrimaryColumns()
    {
        return $this->getOption(self::OPTION_PRIMARY_COLUMNS);
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
     * @param array $ids
     * @return array
     */
    protected function getKvData(array $ids)
    {
        if (empty($ids)) {
            return [];
        }
        $result = [];
        $sql = 'SELECT * FROM ' . self::KV_TABLE_NAME . '
                WHERE ' . self::KV_COLUMN_PARENT_ID . ' IN(' . join(',', array_map(function(){ return '?'; }, $ids)) . ')';
        $secondaryData = $this->getPersistence()->query($sql, $ids)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($secondaryData as $data) {
            $result[$data[self::KV_COLUMN_PARENT_ID]][$data[self::KV_COLUMN_KEY]] = $data[self::KV_COLUMN_VALUE];
        }

        return $result;
    }

    /**
     * @param $parameters
     * @param $selectClause
     * @return string
     */
    /**
     * @param $condition
     * @param $parameters
     * @param $selectClause
     * @return string
     */
    protected function prepareCondition($condition, &$parameters, &$selectClause)
    {
        $whereClause = '';

        //if condition is [ [ key => val ] ] then flatten to [ key => val ] 
        if (is_array($condition) && count($condition) === 1 && is_array(current($condition)) && gettype(array_keys($condition)[0]) == 'integer' ) {
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
            } else if(is_array($value)){
                $op = 'IN (' . join(',', array_map(function(){ return '?'; }, $value)) . ')';
            } else if (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|LIKE|ILIKE|NOT\sLIKE|NOT\sILIKE))?(.*)$/', $value, $matches)) {
                $value = $matches[2];
                $op = $matches[1] ? $matches[1] : "=";
                $op .= ' ?';
            }

            if (in_array($key, $primaryColumns)) {
                $whereClause .= " t.$key $op ";
            } else {
                $joinNum = count($this->joins);
                $whereClause .= " (kv_t_$joinNum.monitoring_key = ? AND kv_t_$joinNum.monitoring_value $op) ";

                $this->joins[] = "LEFT JOIN " . self::KV_TABLE_NAME . " kv_t_$joinNum ON kv_t_$joinNum." . self::KV_COLUMN_PARENT_ID . " = t." . self::COLUMN_DELIVERY_EXECUTION_ID;
                $parameters[] = trim($key);
            }

            if(is_array($value)){
               $parameters = array_merge($parameters, $value);
            } else if ($value !== null) {
                $parameters[] = trim($value);
            }
        }
        return $whereClause;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    /**
     * Check if record for delivery execution already exists in the storage.
     * @todo add isNewRecord property to DeliveryMonitoringDataInterface to prevent repeated queries to DB.
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    protected function isNewRecord(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();
        $deliveryExecutionId = $data[self::COLUMN_DELIVERY_EXECUTION_ID];

        $sql = "SELECT EXISTS( " . PHP_EOL .
            "SELECT " . self::COLUMN_DELIVERY_EXECUTION_ID . PHP_EOL .
            "FROM " . self::TABLE_NAME . PHP_EOL .
            "WHERE " . self::COLUMN_DELIVERY_EXECUTION_ID . "=?)";
        $exists = $this->getPersistence()->query($sql, [$deliveryExecutionId])->fetch(\PDO::FETCH_COLUMN);

        return !((boolean) $exists);
    }

    /**
     * @param string $sortBy
     * @return string
     */
    public static function getSortByColumn($sortBy)
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

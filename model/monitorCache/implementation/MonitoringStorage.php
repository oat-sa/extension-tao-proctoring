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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\oatbox\service\ConfigurableService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

/**
 * Class DeliveryMonitoringService
 *
 * Usage example:
 *
 * Save trying first to UPDATE, then INSERT if update fails
 * ----
 *
 * ```php
 * $data = new DeliveryMonitoringData($deliveryExecution, []);
 * $data->addValue('new_key', 'new_value');
 * $deliveryMonitoringService->partialSave($data);
 * ```
 *
 * Save new record using INSERT
 * ----
 *
 * ```php
 * $data = new DeliveryMonitoringData($deliveryExecution, []);
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
    use OntologyAwareTrait;

    const OPTION_PERSISTENCE = 'persistence';

    const OPTION_USE_UPDATE_MULTIPLE = 'use_update_multiple';

    const OPTION_CACHE_SIZE = 'cache_size';

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
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $data
     * @return DeliveryMonitoringData
     * @throws \common_exception_NotFound
     */
    public function createMonitoringData(DeliveryExecutionInterface $deliveryExecution, $data = [])
    {
        $data = array_merge([
            DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier(),
        ], $data);

        if (!array_key_exists(DeliveryMonitoringService::STATUS, $data)) {
            $data[DeliveryMonitoringService::STATUS] = $deliveryExecution->getState()->getUri();
        }

        $monitoringData = new DeliveryMonitoringData($deliveryExecution, $data);
        $this->propagate($monitoringData);

        return $monitoringData;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringService::getData()
     */
    public function getData(DeliveryExecutionInterface $deliveryExecution)
    {
        $data = $this->loadData($deliveryExecution->getIdentifier());
        $data = $data == false ? [] : $data;
        return $this->buildData($deliveryExecution, $data);
    }

    /**
     * Ensure that all DeliveryMonitoringData are unique
     * per delivery execution id
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param array $data
     * @return DeliveryMonitoringData
     * @throws \common_exception_NotFound
     */
    protected function buildData(DeliveryExecutionInterface $deliveryExecution, $data)
    {
        $dataObject = $this->createMonitoringData($deliveryExecution, $data);

        return $dataObject;
    }

    /**
     * Load data instead of searching
     * Returns false on failure
     * @param string $deliveryExecutionId
     * @return array
     */
    protected function loadData($deliveryExecutionId)
    {
        $qb = $this->getPersistence()->getPlatForm()->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(self::DELIVERY_EXECUTION_ID.'= :deid')
            ->setParameter('deid', $deliveryExecutionId);
        $data = $qb->execute()->fetch(\PDO::FETCH_ASSOC);
        $kvData = $this->getKvData([$deliveryExecutionId]);
        if (isset($kvData[$deliveryExecutionId])) {
            $data =  array_merge($data, $kvData[$deliveryExecutionId]);
        }
        return $data;
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
        $this->selectColumns = $this->getPrimaryColumns();
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
                $result[] = $this->buildData($deliveryExecution, $row);
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
     * @return bool|mixed
     * @throws \Exception
     */
    public function save(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $result = false;
        if ($deliveryMonitoring->validate()) {
            try {
                // we should be ready for unique violation error when the calling side calls
                // save() instead of partialSave()
                $result = $this->create($deliveryMonitoring);
            } catch (\PDOException $e) {
                // when the PDO implementation of RDS is used as a persistence
                // unfortunately the exception is very broad so it can cover more than intended cases
            } catch (UniqueConstraintViolationException $e) {
                // when the DBAL implementation of RDS is used as a persistence
            }
            if (!$result) {
                $this->update($deliveryMonitoring);
            }
            $this->saveKvData($deliveryMonitoring);
            $result = true;
        }
        return $result;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return bool|mixed
     * @throws \Exception
     */
    public function partialSave(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $result = false;
        if ($deliveryMonitoring->validate()) {
            $rowsUpdated = $this->update($deliveryMonitoring);
            if ($rowsUpdated === 0) {
                // doesn't mean an error for sure, cause persistence may return the number of rows actually changed,
                // and not the number of rows matched by the where clause.
                // So just in case try to create without fallback
                try {
                    $this->create($deliveryMonitoring);
                } catch (\PDOException $e) {
                    // when the PDO implementation of RDS is used as a persistence
                } catch (UniqueConstraintViolationException $e) {
                    // when the DBAL implementation of RDS is used as a persistence
                }
            }
            $this->saveKvData($deliveryMonitoring);
            $result = true;
        }

        return $result;
    }

    /**
     * Create new record
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     * @throws \Exception
     */
    protected function create(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();

        $primaryTableData = $this->extractPrimaryData($data);

        $result = $this->getPersistence()->insert(self::TABLE_NAME, $primaryTableData) === 1;

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

        $rowsUpdated = $this->getPersistence()->exec($sql, $params);

        return $rowsUpdated;
    }

    /**
     * Delete all related records from secondary table
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @throws \Exception
     */
    protected function saveKvData(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $data = $deliveryMonitoring->get();

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
        $dataToBeInserted = [];
        $dataToBeUpdated = [];
        foreach ($kvTableData as $kvDataKey => $kvDataValue) {
            if (isset($existent[$kvDataKey]) && $existent[$kvDataKey] == $kvDataValue) {
                continue;
            }

            if (array_key_exists($kvDataKey, $existent)) {
                if ($this->getOption(static::OPTION_USE_UPDATE_MULTIPLE) === true) {
                    $dataToBeUpdated[] = [
                        'conditions' => [
                            self::KV_COLUMN_PARENT_ID => $id,
                            self::KV_COLUMN_KEY => $kvDataKey,
                        ],
                        'updateValues' => [
                            self::KV_COLUMN_VALUE => $kvDataValue
                        ]
                    ];
                } else {
                    $this->getPersistence()->exec(
                        'UPDATE ' . self::KV_TABLE_NAME . '
                              SET ' . self::KV_COLUMN_VALUE . ' = ?
                            WHERE ' . self::KV_COLUMN_PARENT_ID . ' = ?
                              AND ' . self::KV_COLUMN_KEY . ' = ?;',
                        [$kvDataValue, $id, $kvDataKey]
                    );
                }
            } else {
                $dataToBeInserted[] = [
                    self::KV_COLUMN_PARENT_ID => $id,
                    self::KV_COLUMN_KEY => $kvDataKey,
                    self::KV_COLUMN_VALUE => $kvDataValue,
                ];
            }
        }


        if ($this->getOption(static::OPTION_USE_UPDATE_MULTIPLE) === true && !empty($dataToBeUpdated)) {
            $this->getPersistence()->updateMultiple(self::KV_TABLE_NAME, $dataToBeUpdated);
        }

        if (!empty($dataToBeInserted)) {
            $this->getPersistence()->insertMultiple(self::KV_TABLE_NAME, $dataToBeInserted);
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

        $sql = 'DELETE FROM ' . self::KV_TABLE_NAME . '
                WHERE ' . self::KV_COLUMN_PARENT_ID . '=?';
        $this->getPersistence()->exec($sql, [$data[self::COLUMN_DELIVERY_EXECUTION_ID]]);
        $result = true;

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
    public function getPersistence()
    {
        return $this->getServiceLocator()
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
            $toLower = false;

            if ($value === null) {
                $op = 'IS NULL';
            } elseif(is_array($value)){
                $op = 'IN (' . join(',', array_map(function(){ return '?'; }, $value)) . ')';
            } elseif (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|LIKE|ILIKE|NOT\sLIKE|NOT\sILIKE))?(.*)$/', $value, $matches)) {
                if (!empty($matches[1]) && preg_grep('/' . $matches[1] .'/i', ['like','ilike'])) {
                    $toLower = true;
                    $op = 'LIKE';
                } elseif (!empty($matches[1]) && preg_grep('/' . $matches[1] .'/i', ['not like','not ilike'])) {
                    $toLower = true;
                    $op = 'NOT LIKE';
                } else {
                    $op = $matches[1] ? $matches[1] : '=';
                }
                $op .= ' ? ';
                $value = $toLower ? strtolower($matches[2]) : $matches[2];
            }

            if (in_array($key, $primaryColumns)) {
                $whereClause .= $toLower ? " LOWER(t.$key) " : " t.$key ";
                $whereClause .= $op;
            } else {
                $joinNum = count($this->joins);
                $whereClause .= " (kv_t_$joinNum.monitoring_key = ? AND ";
                $whereClause .= $toLower ? "LOWER(kv_t_$joinNum.monitoring_value)" : "kv_t_$joinNum.monitoring_value";
                $whereClause .= " $op) ";

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

    /**
     * @return mixed
     */
    public function getCountOfStatistics()
    {
        $groupedQueryBuilder = $this->getQueryBuilder();
        $groupedQueryBuilder->select('delivery_monitoring.delivery_id');
        $groupedQueryBuilder->from('delivery_monitoring');
        $groupedQueryBuilder->groupBy('delivery_monitoring.delivery_id');
        $groupedSql = $groupedQueryBuilder->getSQL();

        $countQueryBuilder = $this->getQueryBuilder();
        $countQueryBuilder->select('count(grouped.delivery_id)');
        $countQueryBuilder->from('('.$groupedSql.')', 'grouped');
        $stmt = $this->getPersistence()->query($countQueryBuilder->getSQL());
        $count = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $count;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param string $orderby
     * @param string $orderdir
     * @return mixed|void
     */
    public function getStatusesStatistic($limit = 10, $offset = 0, $orderby = 'label', $orderdir = 'asc')
    {
        $statusesList = [
            $this->getResource(ProctoredDeliveryExecution::STATE_ACTIVE),
            $this->getResource(ProctoredDeliveryExecution::STATE_AUTHORIZED),
            $this->getResource(ProctoredDeliveryExecution::STATE_AWAITING),
            $this->getResource(ProctoredDeliveryExecution::STATE_CANCELED),
            $this->getResource(ProctoredDeliveryExecution::STATE_FINISHED),
            $this->getResource(ProctoredDeliveryExecution::STATE_PAUSED),
            $this->getResource(ProctoredDeliveryExecution::STATE_TERMINATED)
        ];
        $statusesMap = [];
        foreach ($statusesList as $status) {
            $statusesMap[$status->getLabel()] = $status->getUri();
        }

        $paramsValues = [];
        $limitQueryBuilder = $this->getQueryBuilder();
        $limitQueryBuilder->select('limit_q.delivery_id');
        $limitQueryBuilder->groupBy('limit_q.delivery_id');

        if (isset($statusesMap[$orderby])) {
            $innerQueryBuilder = $this->getQueryBuilder();
            $innerQueryBuilder->select('delivery_monitoring.delivery_id');
            $innerQueryBuilder->addSelect('COALESCE( order_join.order_status, 0 ) as order_val');
            $innerQueryBuilder->from('delivery_monitoring');

            $statusBuilder = $this->getQueryBuilder();
            $statusBuilder->select('status_d_m.delivery_id, count(status_d_m.status) order_status');
            $statusBuilder->from('delivery_monitoring', 'status_d_m');
            $statusBuilder->where('status_d_m.status = :status_order');
            $paramsValues[':status_order'] =  $statusesMap[$orderby];
            $statusBuilder->groupBy('status_d_m.delivery_id');
            $statusSql = $statusBuilder->getSQL();

            $innerQueryBuilder->leftJoin(
                'delivery_monitoring',
                '('.$statusSql.')',
                'order_join',
                'order_join.delivery_id=delivery_monitoring.delivery_id'
            );
            $innerQueryBuilder->groupBy('delivery_monitoring.delivery_id, order_val');
            $innerQueryBuilder->orderBy('order_val', $orderdir);

            if ($limit) {
                $innerQueryBuilder->setMaxResults($limit);
            }
            $innerQueryBuilder->setFirstResult($offset);

            $innerSql = $innerQueryBuilder->getSQL();
            $limitQueryBuilder->from('('.$innerSql.')', 'limit_q');
            $limitQueryBuilder->addGroupBy('limit_q.order_val');
            $limitQueryBuilder->orderBy('order_val', $orderdir);
        } else if($orderby == 'label') {
            $limitQueryBuilder->from('delivery_monitoring', 'limit_q');
            $limitQueryBuilder->addSelect('limit_q.delivery_name');
            $limitQueryBuilder->addGroupBy('limit_q.delivery_name');
            $limitQueryBuilder->orderBy('limit_q.delivery_name', $orderdir);
            if ($limit) {
                $limitQueryBuilder->setMaxResults($limit);
            }
            $limitQueryBuilder->setFirstResult($offset);
        } else {
            $limitQueryBuilder->from('delivery_monitoring', 'limit_q');
            $limitQueryBuilder->addSelect('max(limit_q.start_time) as max_start_time');
            $limitQueryBuilder->orderBy('max_start_time', $orderdir);
            if ($limit) {
                $limitQueryBuilder->setMaxResults($limit);
            }
            $limitQueryBuilder->setFirstResult($offset);
        }
        $limitQueryBuilder->andWhere('limit_q.delivery_id IS NOT NULL');
        $limitSql = $limitQueryBuilder->getSQL();
        $stmtLimit = $this->getPersistence()->query($limitSql, $paramsValues);
        $dataLimit = $stmtLimit->fetchAll(\PDO::FETCH_COLUMN);


        $queryBuilder = $this->getQueryBuilder();
        $conn = $queryBuilder->getConnection();
        $queryBuilder->select('delivery_m.delivery_id, delivery_m.delivery_name');

        foreach ($statusesMap as $label => $statusUri) {
            $queryBuilder->addSelect('count('.$conn->quoteIdentifier('s_'.$label).'.status) as ' . $conn->quoteIdentifier($label));
        }

        $queryBuilder->addSelect('max('.$conn->quoteIdentifier('last_launch').'.start_time) as ' . $conn->quoteIdentifier(__('Last launch')));

        $queryBuilder->from(self::TABLE_NAME, 'delivery_m');

        $paramsValues = [];
        $statusNum = 0;
        foreach ($statusesMap as $label => $statusUri) {
            $queryBuilder->leftJoin(
                'delivery_m',
                self::TABLE_NAME,
                $conn->quoteIdentifier('s_'.$label),
                'delivery_m.delivery_execution_id='.$conn->quoteIdentifier('s_'.$label).'.delivery_execution_id and '
                .$conn->quoteIdentifier('s_'.$label).'.status = :status_uri_'.$statusNum
            );
            $paramsValues[':status_uri_'.$statusNum] = $statusUri;
            $statusNum++;
        }
        $queryBuilder->leftJoin(
            'delivery_m',
            self::TABLE_NAME,
            $conn->quoteIdentifier('last_launch'),
            'delivery_m.delivery_execution_id='.$conn->quoteIdentifier('last_launch').'.delivery_execution_id'
        );

        if ($dataLimit) {
            $placeHolders = [];
            foreach ($dataLimit as $index => $value) {
                $key = ':in_condition_' . $index;
                $placeHolders[] = $key;
                $paramsValues[$key] = $value;
            }
            $placeHolders = implode(',', $placeHolders);
            $queryBuilder->where("delivery_m.delivery_id IN ($placeHolders)");
        }

        $queryBuilder->groupBy('delivery_m.delivery_id, delivery_m.delivery_name');

        foreach ($statusesMap as $label => $statusUri) {
            $queryBuilder->addGroupBy($conn->quoteIdentifier('s_'.$label).'.status');
        }

        $outerQueryBuilder = $this->getQueryBuilder();

        $outerQueryBuilder->select('delivery_name as label, delivery_id');

        foreach ($statusesMap as $label => $statusUri) {
            $outerQueryBuilder->addSelect('sum('.$conn->quoteIdentifier($label).') as ' . $conn->quoteIdentifier($label));
        }
        $outerQueryBuilder->addSelect('max('.$conn->quoteIdentifier(__('Last launch')).') as ' .  $conn->quoteIdentifier(__('Last launch')));
        $outerQueryBuilder->from('('.$queryBuilder->getSQL().')', 'delivery_statuses');
        $outerQueryBuilder->groupBy('delivery_id, label');
        $outerQueryBuilder->orderBy($conn->quoteIdentifier($orderby), $orderdir);

        $sql = $outerQueryBuilder->getSQL();

        $stmt = $this->getPersistence()->query($sql, $paramsValues);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $data = $this->getData($request->getDeliveryExecution());
        $return = $this->delete($data);
        $this->deleteKvData($data);

        return $return;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder();
    }
}

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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\model\repository;

use common_exception_NotFound;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use PDO;
use PDOException;

class MonitoringRepository extends ConfigurableService implements DeliveryMonitoringService
{
    use OntologyAwareTrait;

    const OPTION_PERSISTENCE = 'persistence';
    const OPTION_USE_UPDATE_MULTIPLE = 'use_update_multiple';

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

    const COLUMN_EXTRA_DATA = 'extra_data';

    const DEFAULT_SORT_COLUMN = self::COLUMN_ID;
    const DEFAULT_SORT_ORDER = 'ASC';
    const DEFAULT_SORT_TYPE = 'string';

    private $queryParams = [];

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $data
     * @return DeliveryMonitoringData
     * @throws common_exception_NotFound
     */
    public function createMonitoringData(DeliveryExecutionInterface $deliveryExecution, $data = [])
    {
        $data = array_merge([
            DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier(),
        ], $data);

        if (!array_key_exists(DeliveryMonitoringService::STATUS, $data)) {
            $data[DeliveryMonitoringService::STATUS] = $deliveryExecution->getState()->getUri();
        }

        // @todo data object should not use ServiceLocator
        $monitoringData = new DeliveryMonitoringData($deliveryExecution, $data);
        $this->propagate($monitoringData);

        return $monitoringData;
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array|DeliveryMonitoringData
     * @throws common_exception_NotFound
     */
    public function getData(DeliveryExecutionInterface $deliveryExecution): DeliveryMonitoringData
    {
        return $this->createMonitoringData(
            $deliveryExecution,
            $this->loadData($deliveryExecution->getIdentifier())
        );
    }

    /**
     * @param array $criteria
     * @param array $options
     * @return DeliveryMonitoringData[]
     * @throws common_exception_NotFound
     */
    public function find(array $criteria = [], array $options = []): array
    {
        $this->queryParams = [];

        $whereClause = $this->prepareCondition($criteria, $this->queryParams);
        if ($whereClause !== '') {
            $whereClause = 'WHERE ' . $whereClause;
        }

        $defaultOptions = [
            'order' => join(' ', [static::DEFAULT_SORT_COLUMN, static::DEFAULT_SORT_ORDER, static::DEFAULT_SORT_TYPE]),
            'offset' => 0,
            'asArray' => false
        ];
        $options = array_merge($defaultOptions, $options);

        $orderClause = $this->prepareOrderStmt($options['order']);
        if ($orderClause !== '') {
            $orderClause = 'ORDER BY ' . $orderClause;
        }

        $sql = sprintf(
            'SELECT %s FROM %s t %s %s',
            implode(', ', $this->getPrimaryColumns()),
            self::TABLE_NAME,
            $whereClause,
            $orderClause
        );

        if (isset($options['limit']))  {
            $sql = $this->getPersistence()->getPlatForm()->limitStatement($sql, $options['limit'], $options['offset']);
        }

        $stmt = $this->getPersistence()->query($sql, $this->queryParams);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($data as &$row) {
            $extraData = [];
            if (isset($row[self::COLUMN_EXTRA_DATA])) {
                $decodedExtraData = json_decode($row[self::COLUMN_EXTRA_DATA], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $extraData = $decodedExtraData;
                }
            }
            unset($row[self::COLUMN_EXTRA_DATA]);
            $row = array_merge($row, $extraData);

            if (!$options['asArray']) {
                $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($row[self::COLUMN_DELIVERY_EXECUTION_ID]);
                $row = $this->createMonitoringData($deliveryExecution, $row);
            }
        }

        return $data;
    }

    public function count(array $criteria = []): int
    {
        $this->queryParams = [];

        $whereClause = $this->prepareCondition($criteria, $this->queryParams);
        if ($whereClause !== '') {
            $whereClause = 'WHERE ' . $whereClause;
        }

        $sql = sprintf('select count(*) from %s t %s', self::TABLE_NAME, $whereClause);

        $stmt = $this->getPersistence()->query($sql, $this->queryParams);
        $result = $stmt->fetch(PDO::FETCH_BOTH);

        return (int) $result[0];
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return bool|mixed
     * @throws Exception
     */
    public function save(DeliveryMonitoringDataInterface $deliveryMonitoring): bool
    {
        $result = false;
        if ($deliveryMonitoring->validate()) {
            try {
                // we should be ready for unique violation error when the calling side calls
                // save() instead of partialSave()
                $result = $this->create($deliveryMonitoring);
            } catch (PDOException $e) {
                // when the PDO implementation of RDS is used as a persistence
                // unfortunately the exception is very broad so it can cover more than intended cases
            } catch (UniqueConstraintViolationException $e) {
                // when the DBAL implementation of RDS is used as a persistence
            }
            if (!$result) {
                $this->update($deliveryMonitoring);
            }

            $result = true;
        }

        return $result;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return bool
     * @throws Exception
     */
    public function partialSave(DeliveryMonitoringDataInterface $deliveryMonitoring): bool
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
                } catch (PDOException $e) {
                    // when the PDO implementation of RDS is used as a persistence
                } catch (UniqueConstraintViolationException $e) {
                    // when the DBAL implementation of RDS is used as a persistence
                }
            }

            $result = true;
        }

        return $result;
    }

    /**
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean
     */
    public function delete(DeliveryMonitoringDataInterface $deliveryMonitoring): bool
    {
        $data = $deliveryMonitoring->get();

        $sql = sprintf('DELETE FROM %s WHERE %s = ?', self::TABLE_NAME, self::COLUMN_DELIVERY_EXECUTION_ID);

        return $this->getPersistence()->exec($sql, [$data[self::COLUMN_DELIVERY_EXECUTION_ID]]) === 1;
    }

    /**
     * @return common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        return $this->getServiceLocator()
            ->get(PersistenceManager::SERVICE_ID)
            ->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }

    /**
     * @todo extract this method to another service with statistic responsabilities
     *
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
        return $stmt->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * @todo extract this method to another service with statistic responsibilities
     * @todo query of 150 lines, erf
     *
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
        $dataLimit = $stmtLimit->fetchAll(PDO::FETCH_COLUMN);


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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request): bool
    {
        return $this->delete(
            $this->getData($request->getDeliveryExecution())
        );
    }

    /**
     * @param $order
     * @return string
     */
    protected function prepareOrderStmt($order): string
    {
        $order = explode(',', $order);
        $result = [];
        foreach ($order as $orderRule) {
            $orderStmt = $this->buildSingleOrderRule($orderRule);
            if ($orderStmt) {
                $result[] = $orderStmt;
            }
        }

        return implode(', ', $result);
    }

    private function buildSingleOrderRule(string $orderRule): string
    {
        if (!preg_match(
            '/([a-z_][a-z0-9_]*)\s?(asc|desc)?\s?(string|numeric)?/i',
            $orderRule,
            $ruleParts
        )) {
            return '';
        }

        $orderBy = $ruleParts[1];
        $order = $ruleParts[2] ?? 'ASC';
        $type = $ruleParts[3] ?? null;

        if (!in_array($orderBy, $this->getPrimaryColumns(), true)) {
            $colName = $orderBy;
            if (in_array($this->getPlatformName(), ['mysql', 'sqlite'])) {
                $colName = sprintf('JSON_EXTRACT(t.%s, \'$.%s\')', self::COLUMN_EXTRA_DATA, $colName);
            } else {
                $colName = sprintf('t.%s ->> \'%s\'', self::COLUMN_EXTRA_DATA, $colName);
            }
            $sortingColumn = $colName;
        } else {
            $sortingColumn = $orderBy;
        }

        return $type === 'numeric'
            ? $this->buildNumericOrderWithCastingToDecimal($sortingColumn, $order)
            : sprintf('%s %s', $sortingColumn, $order);
    }

    /**
     * to cover cases when numeric order requested for not numeric fields
     */
    private function buildNumericOrderWithCastingToDecimal(string $sortingColumn, string $direction): string
    {
        return in_array($this->getPlatformName(), ['mysql', 'sqlite'])
            ? sprintf("cast(%s as DECIMAL) %s", $sortingColumn, $direction)
            : sprintf("cast(NULLIF(regexp_replace(%s, '\D', '', 'g'), '') as decimal) %s", $sortingColumn, $direction);
    }

    /**
     * Load data instead of searching
     *
     * @param string $deliveryExecutionId
     * @return array
     */
    private function loadData(string $deliveryExecutionId): array
    {
        $qb = $this->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(self::DELIVERY_EXECUTION_ID.'= :id')
            ->setParameter('id', $deliveryExecutionId);

        $data = $qb->execute()->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            $data = [];
        } else {
            if (isset($data[self::COLUMN_EXTRA_DATA])) {
                $extraData = json_decode($data[self::COLUMN_EXTRA_DATA], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = array_merge($data, $extraData);
                }
            }
            unset($data[self::COLUMN_EXTRA_DATA]);
        }

        return $data;
    }

    /**
     * Create new record
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     * @throws Exception
     */
    private function create(DeliveryMonitoringDataInterface $deliveryMonitoring): bool
    {
        $data = $deliveryMonitoring->get();

        $primaryTableData = $this->extractPrimaryData($data);

        $extraData = $this->extractKvData($data);

        $types[self::COLUMN_EXTRA_DATA] = (in_array($this->getPlatformName(), ['mysql','sqlite'])) ? 'json' : 'jsonb';
        $primaryTableData[self::COLUMN_EXTRA_DATA] = json_encode($extraData);

        return $this->getPersistence()->insert(self::TABLE_NAME, $primaryTableData, $types) === 1;
    }

    /**
     * Update existing record
     * @param DeliveryMonitoringDataInterface $deliveryMonitoring
     * @return boolean whether data is saved
     */
    private function update(DeliveryMonitoringDataInterface $deliveryMonitoring)
    {
        $params = [':delivery_execution_id' => $deliveryMonitoring->get()[self::COLUMN_DELIVERY_EXECUTION_ID]];

        $data = $deliveryMonitoring->get();
        $extraData = $this->extractKvData($data);

        $primaryTableData = $this->extractPrimaryData($data);

        unset($primaryTableData['delivery_execution_id']);
        $setClauses = [];
        foreach ($primaryTableData as $dataKey => $dataValue) {
            $setClauses[] = sprintf('%s = :%s', $dataKey, $dataKey);
            $params[sprintf(':%s', $dataKey)] = $dataValue;
        }

        $setExtraDataClauses = [];
        $platformName = $this->getPlatformName();
        foreach ($extraData as $extraDataKey => $extraDataValue) {
            if (in_array($platformName, ['mysql','sqlite'])) {
                $setExtraDataClauses[] = sprintf('\'$.%s\', :%s', $extraDataKey, $extraDataKey);
            } else {
                $setExtraDataClauses[] = sprintf('jsonb_build_object(\'%s\', :%s::jsonb)', $extraDataKey, $extraDataKey);
                $extraDataValue = json_encode($extraDataValue);
            }
            $params[sprintf(':%s', $extraDataKey)] = $extraDataValue;
        }

        if (!empty($setExtraDataClauses)) {
            if (in_array($platformName, ['mysql','sqlite'])) {
                $setClauses[] = sprintf(
                    '%s = json_set(COALESCE(%s, \'{}\'), %s)',
                    self::COLUMN_EXTRA_DATA, self::COLUMN_EXTRA_DATA, implode(', ', $setExtraDataClauses)
                );
            } else {
                $setClauses[] = sprintf(
                    '%s = CASE WHEN %s IS NULL THEN %s ELSE %s || %s END',
                    self::COLUMN_EXTRA_DATA,
                    self::COLUMN_EXTRA_DATA,
                    implode(' || ', $setExtraDataClauses),
                    self::COLUMN_EXTRA_DATA,
                    implode(' || ', $setExtraDataClauses)
                );
            }
        }

        $setClause = implode(', ', $setClauses);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s  = :delivery_execution_id',
            self::TABLE_NAME, $setClause, self::COLUMN_DELIVERY_EXECUTION_ID
        );

        return $this->getPersistence()->exec($sql, $params);
    }

    /**
     * Get list of table column names
     * @return array
     */
    private function getPrimaryColumns()
    {
        return $this->getOption(self::OPTION_PRIMARY_COLUMNS);
    }

    /**
     * @todo cast data to object
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

    /**
     * @param $condition
     * @param $parameters
     * @param $selectClause
     * @return string
     */
    private function prepareCondition($condition, &$parameters)
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
                $whereClause .=  $this->prepareCondition($subCondition, $parameters);
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
            } elseif (preg_match('/^(?:\s*(<>|<=|>=|<|>|=|LIKE|ILIKE|NOT\sLIKE|NOT\sILIKE))?(.*)$/', (string)$value, $matches)) {
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
                $value = trim($toLower ? strtolower($matches[2]) : $matches[2]);
            }

            if (in_array($key, $primaryColumns)) {
                $whereClause .= $toLower ? " LOWER(t.$key) " : " t.$key ";
                $whereClause .= $op;
            } else if (in_array($this->getPlatformName(), ['mysql','sqlite'])) {
                $whereClause .= sprintf(' JSON_EXTRACT(t.%s, \'$.%s\') %s ', self::COLUMN_EXTRA_DATA, trim($key), $op);
            } else {
                $isLikeSearch = isset($op) && stripos($op, 'like') !== false;
                $value = is_array($value) ? $value : [$value];
                if ($isLikeSearch) {
                    $jsonDataAccessOperator = '->>';
                } else {
                    $jsonDataAccessOperator = '->';
                    $value = array_map('json_encode', $value);
                }
                $whereClause .= sprintf(
                    ' t.%s %s \'%s\' %s ',
                    self::COLUMN_EXTRA_DATA,
                    $jsonDataAccessOperator,
                    trim($key),
                    $op
                );
            }

            if(is_array($value)){
               $parameters = array_merge($parameters, $value);
            } else if ($value !== null) {
                $parameters[] = trim($value);
            }
        }
        return $whereClause;
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder();
    }

    private function getPlatformName(): string
    {
        return $this->getPersistence()->getPlatForm()->getName();
    }
}

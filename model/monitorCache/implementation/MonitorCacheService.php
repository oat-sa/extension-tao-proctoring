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

use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService as DeliveryMonitoringServiceInterface;

/**
 * Class MonitorCacheService
 *
 * RDS Implementation of DeliveryMonitoringService without using autoincrement fields and PDO::lastInsertId function.
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class MonitorCacheService extends DeliveryMonitoringService
{

    const COLUMN_ID = DeliveryMonitoringServiceInterface::DELIVERY_EXECUTION_ID;

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
}

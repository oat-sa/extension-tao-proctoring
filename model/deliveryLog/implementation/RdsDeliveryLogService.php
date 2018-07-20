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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\deliveryLog\implementation;

use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\oatbox\service\ConfigurableService;

/**
 * Interface DeliveryLog
 *
 * @package oat\taoProctoring\model\deliveryLog
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class RdsDeliveryLogService extends ConfigurableService implements DeliveryLog
{
    const OPTION_PERSISTENCE = 'persistence';
    const TABLE_NAME = 'delivery_log';
    const ID = 'id';

    const OPTION_FIELDS = 'fields';
    /**
     * Log delivery execution data.
     * Notice that `$data` parameter will be encoded to JSON before saving
     *
     * @param string $deliveryExecutionId
     * @param string $eventId
     * @param mixed $data
     * @param string $user user identifier
     * @return boolean
     */
    public function log($deliveryExecutionId, $eventId, $data, $user = null)
    {
        $data = $this->encodeData($data);

        if ($user === null) {
            $user = \common_session_SessionManager::getSession()->getUser()->getIdentifier();
        }

        if (empty($user) && PHP_SAPI == 'cli') {
            $user = 'cli';
        }

        $result = $this->getPersistence()->insert(
            self::TABLE_NAME,
            array(
                self::DELIVERY_EXECUTION_ID => $deliveryExecutionId,
                self::EVENT_ID => $eventId,
                self::DATA => $data,
                self::CREATED_AT => microtime(true),
                self::CREATED_BY => $user,
            )
        );

        return $result === 1;
    }

    /**
     * Get logged data by delivery execution id
     *
     * @param string $deliveryExecutionId
     * @param sting|null $eventId - filter data by event id
     * @return mixed
     */
    public function get($deliveryExecutionId, $eventId = null)
    {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " t " . PHP_EOL;
        $sql .= "WHERE " . self::DELIVERY_EXECUTION_ID . "=? ";

        $parameters = [$deliveryExecutionId];

        if ($eventId !== null) {
            $sql .= "AND " . self::EVENT_ID . "=? ";
            $parameters[] = $eventId;
        }

        $sql .= "ORDER BY " . self::ID . " ASC";

        $stmt = $this->getPersistence()->query($sql, $parameters);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = $this->decodeValues($data);

        return $result;
    }


    public function flush()
    {
        $query = 'TRUNCATE ' . self::TABLE_NAME;

        try{
            $this->getPersistence()->exec($query);
        } catch (\PDOException $e){
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $sql = 'DELETE FROM ' . static::TABLE_NAME  . ' WHERE ' . self::DELIVERY_EXECUTION_ID . '= ? ';
        $parameters = [$request->getDeliveryExecution()->getIdentifier()];

        $stmt = $this->getPersistence()->exec($sql, $parameters);

        return $stmt;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function insertMultiple(array $data)
    {
        return $this->getPersistence()->insertMultiple(self::TABLE_NAME, $data);
    }

    /**
     * @param array $params
     *  [
     *      'delivery_execution_id' => '',
     *      'event_id' => '',
     *      'from' => '',
     *      'to' => '',
     *      'created_by' => '',
     *  ]
     * @param array $options
     *  [
     *      'order' => 'created_at',
     *      'dir' => 'asc',
     *      'limit' => null, // to get all records
     *      'offset' => 0,
     *  ]
     * @param bool $shouldDecodeData
     * @return mixed
     */
    public function search($params = [], $options = [], $shouldDecodeData = true)
    {
        $sql = 'SELECT * FROM ' . static::TABLE_NAME . ' WHERE ';
        $fields = $this->getFields();
        $parameters = [];
        $where = [];
        foreach ($params as $key => $val) {
            if (in_array($key, $fields)) {
                $where[] = $key . '= ?';
                $parameters[] = $val;
            }
        }

        if (isset($params['from'])) {
            $where[] = self::CREATED_AT . ' >= ?';
            $parameters[] = $params['from'];
        }

        if (isset($params['to'])) {
            $where[] = self::CREATED_AT . ' <= ?';
            $parameters[] = $params['to'];
        }

        $sql .= implode(' AND ', $where);
        $opts = [
            'order' => 'ORDER BY ?',
            'dir' => '?',
            'limit' => 'LIMIT ?',
            'offset' => 'OFFSET ?',
        ];

        foreach ($opts as $k => $v) {
            if (isset($options[$k])) {
                if ($k == 'dir') {
                    $sql .= ' ' . (mb_strtolower($v) == 'desc' ? 'DESC' : 'ASC');
                } else {
                    $sql .= ' ' . $v;
                    $parameters[] = $options[$k];
                }
            }
        }
        $stmt = $this->getPersistence()->query($sql, $parameters);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($shouldDecodeData) {
            $result = $this->decodeValues($data);
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getFields()
    {
        $fields = $this->getOption(static::OPTION_FIELDS);

        return is_null($fields) ? [] : $fields;
    }

    /**
     * @param $data
     * @return array
     */
    private function decodeValues(array $data)
    {
        $result = [];
        foreach ($data as $row) {
            if (isset($row[self::DATA])) {
                $row[self::DATA] = $this->decodeData($row[self::DATA]);
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    protected function decodeData($data)
    {
        return json_decode($data, true);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function encodeData($data)
    {
        return json_encode($data);
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    private function getPersistence()
    {
        return \common_persistence_Manager::getPersistence($this->getOption(self::OPTION_PERSISTENCE));
    }
}

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

    /**
     * Log data
     *
     * @param string $deliveryExecutionId
     * @param string $eventId
     * @param mixed $data
     * @return boolean
     */
    public function log($deliveryExecutionId, $eventId, $data)
    {
        if (!is_string($data) || !$this->isJson($data)) {
            $data = json_encode($data);
        }

        $currentUser = \common_session_SessionManager::getSession()->getUser();

        $result = $this->getPersistence()->insert(
            self::TABLE_NAME,
            array(
                self::DELIVERY_EXECUTION_ID => $deliveryExecutionId,
                self::EVENT_ID => $eventId,
                self::DATA => $data,
                self::CREATED_AT => time(),
                self::CREATED_BY => $currentUser->getIdentifier(),
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
        $sql .= "WHERE " . self::DELIVERY_EXECUTION_ID . "=:delivery_execution_id ";

        $parameters = [
            ':delivery_execution_id' => $deliveryExecutionId
        ];

        if ($eventId !== null) {
            $sql .= "AND " . self::EVENT_ID . "=:event_id ";
            $parameters[':event_id'] = $eventId;
        }

        $sql .= "ORDER BY " . self::ID . " ASC";

        $stmt = $this->getPersistence()->query($sql, $parameters);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = $this->encodeValues($data);

        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    private function encodeValues(array $data)
    {
        $result = [];
        foreach ($data as $row) {
            if (isset($row[self::DATA]) && $this->isJson($row[self::DATA])) {
                $row[self::DATA] = json_decode($row[self::DATA], true);
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    private function getPersistence()
    {
        return \common_persistence_Manager::getPersistence($this->getOption(self::OPTION_PERSISTENCE));
    }

    /**
     * Check whether string is json
     * @param string $string
     * @return bool
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
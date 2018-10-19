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

use Doctrine\DBAL\Query\QueryBuilder;
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
     * @param string|null $eventId - filter data by event id
     * @return mixed
     */
    public function get($deliveryExecutionId, $eventId = null)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
            ->where(static::DELIVERY_EXECUTION_ID . '=:delivery_execution_id')
            ->setParameter('delivery_execution_id', $deliveryExecutionId)
        ;

        if ($eventId !== null) {
            $queryBuilder
                ->andWhere(static::EVENT_ID . '=:event_id')
                ->setParameter('event_id', $eventId);
            ;
        }

        $queryBuilder->orderBy(static::ID, 'ASC');

        $data = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

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
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(static::TABLE_NAME)
            ->where(self::DELIVERY_EXECUTION_ID . '=:delivery_execution_id')
            ->setParameter('delivery_execution_id', $request->getDeliveryExecution()->getIdentifier());

        return ($queryBuilder->execute() > 0);
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
     *      'shouldDecodeData' => true
     *  ]
     * @return mixed
     */
    public function search($params = [], $options = [])
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
        ;

        $fields = $this->getFields();
        foreach ($params as $key => $val) {
            if (in_array($key, $fields)) {
                $queryBuilder->andWhere($key . '= :'.$key);
                $queryBuilder->setParameter($key, $val);
            }
        }

        if (isset($params['from'])) {
            $queryBuilder->andWhere(self::CREATED_AT . ' >=:createdAtMore');
            $queryBuilder->setParameter('createdAtMore', $params['from']);
        }

        if (isset($params['to'])) {
            $queryBuilder->andWhere(self::CREATED_AT . ' <=:createdAtLess');
            $queryBuilder->setParameter('createdAtLess', $params['to']);
        }

        if (isset($options['limit'])) {
            $queryBuilder->setMaxResults($options['limit']);
        }

        if (isset($options['offset'])) {
            $queryBuilder->setFirstResult($options['offset']);
        }

        if (isset($options['order']) && isset($options['dir'])) {
            $queryBuilder->orderBy($options['order'], strtoupper($options['dir']));
        }

        $shouldDecodeData = isset($options['shouldDecodeData']) ? (bool)$options['shouldDecodeData'] : true;
        $data             = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
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

    /**
     * @return QueryBuilder
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatform()->getQueryBuilder();
    }
}

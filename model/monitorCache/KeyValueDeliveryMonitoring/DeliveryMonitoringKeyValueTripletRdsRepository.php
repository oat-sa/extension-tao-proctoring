<?php

namespace oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring;

use common_persistence_SqlPersistence;
use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringKeyValueTripletRdsRepository extends ConfigurableService implements DeliveryMonitoringKeyValueTripletRepository
{
    const OPTION_PERSISTENCE = 'persistence';

    /** @var DeliveryMonitoringKeyValueTripletCollection */
    private $inMemoryCacheCollection;

    /**
     * @return common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        return $this->getServiceManager()
            ->get(\common_persistence_Manager::SERVICE_ID)
            ->getPersistenceById($this->getOption(static::OPTION_PERSISTENCE));
    }
    
    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function insertCollection(DeliveryMonitoringKeyValueTripletCollection $collection)
    {
        if ($collection->isEmpty()) {
            return;
        }

        $this->getPersistence()->insertMultiple(MonitoringStorage::KV_TABLE_NAME, $collection->toArray());
        $this->inMemoryCacheCollection = null;
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function updateCollection(DeliveryMonitoringKeyValueTripletCollection $collection)
    {
        if ($collection->isEmpty()) {
            return;
        }
        $keys = [];

        //@TODO move this as a general method for update multiple
        $query = "UPDATE ". MonitoringStorage::KV_TABLE_NAME ." SET ". MonitoringStorage::KV_COLUMN_VALUE ." = CASE";
        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($collection as $item) {
            $keys[] = $item->getKey();
            $query .= " WHEN ". MonitoringStorage::KV_COLUMN_KEY ." = '". $item->getKey() ."' THEN '" . $item->getValue() . "'";
        }

        if (!is_null($this->inMemoryCacheCollection)) {
            $this->inMemoryCacheCollection->markAsUpdatedTripletsByKeys($keys);
        }

        $ids = str_repeat('?,', count($keys) - 1) . '?';
        $query .= " END ";
        $query .= " WHERE ".MonitoringStorage::KV_COLUMN_PARENT_ID." = ? AND ". MonitoringStorage::KV_COLUMN_KEY ." IN($ids)";

        $params = [$item->getDeliveryId()];
        foreach ($keys as $key => $value) {
            $params[] = ${$key} = $value;
        }

        $this->getPersistence()->query($query, $params);
    }

    /**
     * @param string $deliveryId
     * @param array $availableKeys
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function findDeliveryKVCollection($deliveryId, $availableKeys)
    {
        if (!is_null($this->inMemoryCacheCollection) && !$this->inMemoryCacheCollection->isEmpty()) {
            return $this->inMemoryCacheCollection;
        }

        $queryBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();

        $qb = $queryBuilder->select(MonitoringStorage::KV_COLUMN_KEY . ', ' . MonitoringStorage::KV_COLUMN_VALUE)
            ->from(MonitoringStorage::KV_TABLE_NAME)
            ->where(MonitoringStorage::KV_COLUMN_PARENT_ID . '= :' . MonitoringStorage::KV_COLUMN_PARENT_ID)
            ->andWhere(MonitoringStorage::KV_COLUMN_KEY . ' IN(:keys)')
            ->setParameter(MonitoringStorage::KV_COLUMN_PARENT_ID, $deliveryId)
            ->setParameter('keys', $availableKeys, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
        ;

        $rawData = $qb->execute()->fetchAll();
        $factoryData = [];

        foreach ($rawData as $datum) {
            $factoryData[$datum[MonitoringStorage::KV_COLUMN_KEY ]] = $datum[MonitoringStorage::KV_COLUMN_VALUE];
        }

        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection($deliveryId, $factoryData);

        $this->inMemoryCacheCollection = $collection;

        return $collection;
    }

}
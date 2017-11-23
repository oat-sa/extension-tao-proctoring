<?php

namespace oat\taoProctoring\model\monitorCache\implementation\KeyValueDeliveryMonitoring;


use common_persistence_SqlPersistence;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringKeyValueTripletRepository
{
    /** @var common_persistence_SqlPersistence */
    private $persistence;

    /** @var array */
    private $availableKeys;

    /**
     * @param common_persistence_SqlPersistence $persistence
     * @param array $availableKeys
     */
    public function __construct(common_persistence_SqlPersistence $persistence, array $availableKeys)
    {
        $this->persistence = $persistence;
        $this->availableKeys = $availableKeys;
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function insertCollection(DeliveryMonitoringKeyValueTripletCollection $collection)
    {
        $this->persistence->insertMultiple(MonitoringStorage::KV_TABLE_NAME, $collection->toArray());
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function updateCollection(DeliveryMonitoringKeyValueTripletCollection $collection)
    {

    }

    /**
     * @param string $deliveryId
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function findDeliveryCollection($deliveryId)
    {
        $queryBuilder = $this->persistence->getPlatform()->getQueryBuilder();

        $qb = $queryBuilder->select(MonitoringStorage::KV_COLUMN_KEY . ',' . MonitoringStorage::KV_COLUMN_VALUE)
            ->from(MonitoringStorage::KV_TABLE_NAME)
            ->where(MonitoringStorage::KV_COLUMN_PARENT_ID . ' = :' . MonitoringStorage::KV_COLUMN_PARENT_ID)
            ->andWhere(MonitoringStorage::KV_COLUMN_KEY . ' IN(:keys)')
            ->setParameter(MonitoringStorage::KV_COLUMN_PARENT_ID, $deliveryId)
            ->setParameter('keys', $this->availableKeys)
        ;

        $rawData = $qb->execute()->fetchAll();

        return DeliveryMonitoringKeyValueTripletCollection::buildCollection($deliveryId, $rawData);
    }

}
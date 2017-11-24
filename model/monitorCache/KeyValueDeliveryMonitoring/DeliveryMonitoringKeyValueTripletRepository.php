<?php

namespace oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring;

use common_persistence_Persistence;

interface DeliveryMonitoringKeyValueTripletRepository
{
    const SERVICE_ID = 'taoProctoring/DeliveryMonitoringKeyValueTripletRepository';

    /**
     * @return common_persistence_Persistence
     */
    public function getPersistence();

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function insertCollection(DeliveryMonitoringKeyValueTripletCollection $collection);

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     */
    public function updateCollection(DeliveryMonitoringKeyValueTripletCollection $collection);

    /**
     * @param string $deliveryId
     * @param array $availableKeys
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function findDeliveryKVCollection($deliveryId, $availableKeys);
}
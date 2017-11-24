<?php

namespace oat\taoProctoring\model\monitorCache\DeliveryMonitoring;

use common_persistence_Persistence;

interface DeliveryMonitoringRepository
{
    const SERVICE_ID = 'taoProctoring/DeliveryMonitoringRepository';

    /**
     * @param DeliveryMonitoringFactory $monitoringFactory
     */
    public function setMonitoringFactory(DeliveryMonitoringFactory $monitoringFactory);

    /**
     * @return DeliveryMonitoringFactory
     */
    public function getMonitoringFactory();

    /**
     * @return common_persistence_Persistence
     */
    public function getPersistence();

    /**
     * @param string $deliveryId
     * @return DeliveryMonitoringEntity
     */
    public function find($deliveryId);

    /**
     * @param DeliveryMonitoringEntity $entity
     * @return bool
     */
    public function update(DeliveryMonitoringEntity $entity);

    /**
     * @param DeliveryMonitoringEntity $entity
     * @return bool
     */
    public function insert(DeliveryMonitoringEntity $entity);

    /**
     * @param $deliveryId
     * @return bool
     */
    public function exists($deliveryId);
}
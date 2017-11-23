<?php

namespace oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoring;

use common_persistence_SqlPersistence;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringRepository
{
    const TABLE_NAME = MonitoringStorage::TABLE_NAME;

    /** @var common_persistence_SqlPersistence */
    private $persistence;

    /** @var DeliveryMonitoringFactory */
    private $monitoringFactory;

    /**
     * @param common_persistence_SqlPersistence $persistence
     * @param DeliveryMonitoringFactory $monitoringFactory
     */
    public function __construct(
        common_persistence_SqlPersistence $persistence,
        DeliveryMonitoringFactory $monitoringFactory
    ) {
        $this->persistence = $persistence;
        $this->monitoringFactory = $monitoringFactory;
    }

    /**
     * @param string $deliveryId
     * @return DeliveryMonitoringEntity
     */
    public function find($deliveryId)
    {
        $queryBuilder = $this->persistence->getPlatform()->getQueryBuilder();
        $columns = $this->monitoringFactory->getColumns();

        $qb = $queryBuilder
            ->select(implode(',', $columns))
            ->from(static::TABLE_NAME)
            ->where($columns[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID] . ' = :' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID)
            ->setParameter(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, $deliveryId);

        $delivery = $qb->execute()->fetchColumn();

        return $this->monitoringFactory->buildEntityFromRawArray($delivery);
    }

    /**
     * @param DeliveryMonitoringEntity $entity
     * @return bool
     */
    public function update(DeliveryMonitoringEntity $entity)
    {
        $dataAttributes = $entity->getDataAttributes();
        $queryBuilder = $this->persistence->getPlatform()->getQueryBuilder();
        $qb = $queryBuilder->update(static::TABLE_NAME);

        foreach ($this->monitoringFactory->getColumns() as $column) {
            $qb->set($column, ':' . $column);
            $qb->setParameter($column, $dataAttributes[$column]);
        }
        $qb->where(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . ' = :' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID);
        $qb->setParameter(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, $entity->getId());
        $qb->execute();

        return true;
    }
}
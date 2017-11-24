<?php

namespace oat\taoProctoring\model\monitorCache\DeliveryMonitoring;

use common_persistence_SqlPersistence;
use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringSqlRepository extends ConfigurableService implements DeliveryMonitoringRepository
{
    const TABLE_NAME = MonitoringStorage::TABLE_NAME;

    const OPTION_PERSISTENCE = 'persistence';

    /** @var DeliveryMonitoringFactory */
    private $monitoringFactory;

    /** @var bool */
    private $exists;

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
     * @param string $deliveryId
     * @return DeliveryMonitoringEntity
     */
    public function find($deliveryId)
    {
        $queryBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $columns = $this->monitoringFactory->getColumns();

        $qb = $queryBuilder
            ->select(implode(', ', $columns))
            ->from(static::TABLE_NAME)
            ->where(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . ' = :' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID)
            ->setParameter(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, $deliveryId);

        $delivery = $qb->execute()->fetch();

        return $this->monitoringFactory->buildEntityFromRawArray($delivery);
    }

    /**
     * @param DeliveryMonitoringEntity $entity
     * @return bool
     */
    public function update(DeliveryMonitoringEntity $entity)
    {
        $dataAttributes = $entity->getDataAttributes();
        $queryBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $qb = $queryBuilder->update(static::TABLE_NAME);

        foreach ($this->monitoringFactory->getColumns() as $column) {
            $qb->set($column, ':' . $column);
            $qb->setParameter($column, $dataAttributes[$column]);
        }
        $qb->where(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . '= :' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID);
        $qb->setParameter(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, $entity->getId());
        $qb->execute();

        return true;
    }

    /**
     * @param DeliveryMonitoringEntity $entity
     * @return bool
     */
    public function insert(DeliveryMonitoringEntity $entity)
    {
        $dataAttributes = $entity->getDataAttributes();

        $this->getPersistence()->insert(static::TABLE_NAME, $dataAttributes);

        $this->exists = true;

        return true;
    }

    /**
     * @param $deliveryId
     * @return bool
     */
    public function exists($deliveryId)
    {
        if (!is_null($this->exists)) {
            return $this->exists;
        }

        $sql = 'SELECT 
                EXISTS(SELECT '. MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID.' 
                FROM '. self::TABLE_NAME .' 
                WHERE '. MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . '=?)';

        $exists = $this->getPersistence()->query($sql, [$deliveryId])->fetch(\PDO::FETCH_COLUMN);

        $this->exists = $exists;

        return (bool)$exists;
    }

    /**
     * @param DeliveryMonitoringFactory $monitoringFactory
     */
    public function setMonitoringFactory(DeliveryMonitoringFactory $monitoringFactory)
    {
        $this->monitoringFactory = $monitoringFactory;
    }

    /**
     * @return DeliveryMonitoringFactory
     */
    public function getMonitoringFactory()
    {
        return  $this->monitoringFactory ;
    }
}
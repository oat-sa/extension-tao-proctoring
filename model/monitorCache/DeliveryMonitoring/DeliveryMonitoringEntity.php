<?php

namespace oat\taoProctoring\model\monitorCache\DeliveryMonitoring;

class DeliveryMonitoringEntity
{
    /** @var string */
    private $id;

    /** @var array */
    private $dataAttributes;

    /**
     * DeliveryMonitoringEntity constructor.
     * @param string $id
     * @param array $dataAttributes
     */
    public function __construct($id, array $dataAttributes)
    {
        $this->id = $id;
        $this->dataAttributes = $dataAttributes;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getDataAttributes()
    {
        return $this->dataAttributes;
    }

    /**
     * @param DeliveryMonitoringEntity $otherEntity
     * @return bool
     */
    public function equals(DeliveryMonitoringEntity $otherEntity)
    {
        return $this->id === $otherEntity->id && $this->dataAttributes === $otherEntity->getDataAttributes();
    }
}
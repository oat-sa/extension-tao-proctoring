<?php

namespace oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring;

class DeliveryMonitoringKeyValueTriplet
{
    /** @var string */
    private $deliveryId;

    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /** @var bool */
    private $saved = false;

    /**
     * @param string $deliveryId
     * @param string $key
     * @param string $value
     */
    public function __construct($deliveryId, $key, $value)
    {
        $this->deliveryId = $deliveryId;
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getDeliveryId()
    {
        return $this->deliveryId;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param DeliveryMonitoringKeyValueTriplet $triplet
     * @return bool
     */
    public function hasSameKey(DeliveryMonitoringKeyValueTriplet $triplet)
    {
        return $this->deliveryId === $triplet->getDeliveryId()
            && $this->key === $triplet->getKey();
    }

    /**
     * @param DeliveryMonitoringKeyValueTriplet $triplet
     * @return bool
     */
    public function equals(DeliveryMonitoringKeyValueTriplet $triplet)
    {
        return $this->deliveryId === $triplet->getDeliveryId()
                && $this->key === $triplet->getKey()
                && $this->value === $triplet->getValue();
    }

    /**
     * @return bool
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * @param bool $saved
     */
    public function setSaved($saved)
    {
        $this->saved = $saved;
    }
}
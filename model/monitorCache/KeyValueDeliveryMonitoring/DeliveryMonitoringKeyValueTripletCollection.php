<?php

namespace oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring;

use Doctrine\Common\Collections\ArrayCollection;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringKeyValueTripletCollection extends ArrayCollection
{
    /**
     * @param string $deliveryId
     * @param array $rawData
     * @return static
     */
    public static function buildCollection($deliveryId, array $rawData)
    {
        $collection = new static();

        foreach ($rawData as $key => $value) {
            $collection->add(new DeliveryMonitoringKeyValueTriplet($deliveryId, $key, $value));
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($this->getIterator() as $item) {
            $array[] = [
                MonitoringStorage::KV_COLUMN_PARENT_ID => $item->getDeliveryId(),
                MonitoringStorage::KV_COLUMN_KEY => $item->getKey(),
                MonitoringStorage::KV_COLUMN_VALUE => $item->getValue(),
            ];
        }

        return $array;
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $existedCollection
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function diffToGetNewTriplets(DeliveryMonitoringKeyValueTripletCollection $existedCollection)
    {
        if ($existedCollection->isEmpty()) {
            return new static($this->getIterator()->getArrayCopy());
        }

        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($this as $key => $item){

            /** @var DeliveryMonitoringKeyValueTriplet $compareItem */
            foreach ($existedCollection as  $key2 => $compareItem) {
                if ($item->hasSameKey($compareItem)) {
                    $this->remove($key);
                    $existedCollection->removeElement($key2);
                    continue 2;
                }
            }
        }

        return new static($this->getIterator()->getArrayCopy());
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $existedCollection
     *
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function diffToGetUpdatedTriplets(DeliveryMonitoringKeyValueTripletCollection $existedCollection)
    {
        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($this as $key => $item){
            /** @var DeliveryMonitoringKeyValueTriplet $compareItem */
            foreach ($existedCollection as $key2 => $compareItem) {
                if ($item->equals($compareItem) || $compareItem->isSaved()) {
                    $this->remove($key);
                    $existedCollection->remove($key2);
                    break;
                }

                if ($item->hasSameKey($compareItem)) {
                    $existedCollection->remove($key2);
                    continue 2;
                }

            }
        }

        return new static($this->getIterator()->getArrayCopy());
    }

    /**
     * @param array $keys
     */
    public function markAsUpdatedTripletsByKeys(array $keys)
    {
        /** @var  DeliveryMonitoringKeyValueTriplet $item */
        foreach ($this as $index => $item){
            if (in_array($item->getKey(), $keys)) {
                $item->setSaved(true);
                $this->offsetSet($index, $item);
                break;
            }
        }
    }
}
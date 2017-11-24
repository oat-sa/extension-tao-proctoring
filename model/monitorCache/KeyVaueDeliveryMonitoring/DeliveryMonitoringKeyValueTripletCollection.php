<?php

namespace oat\taoProctoring\model\monitorCache\implementation\KeyValueDeliveryMonitoring;

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
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function diffToGetNewTriplets(DeliveryMonitoringKeyValueTripletCollection $collection)
    {
        $copyArray = $this->getIterator()->getArrayCopy();

        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($copyArray as $key => $item){

            /** @var DeliveryMonitoringKeyValueTriplet $compareItem */
            foreach ($collection->getIterator() as $compareItem) {

                if ($item->hasSameKey($compareItem)) {
                    unset($copyArray[$key]);
                    break;
                }
            }
        }

        return new static($copyArray);
    }

    /**
     * @param DeliveryMonitoringKeyValueTripletCollection $collection
     * @return DeliveryMonitoringKeyValueTripletCollection
     */
    public function diffToGetUpdatedTriplets(DeliveryMonitoringKeyValueTripletCollection $collection)
    {
        $copyArray = $this->getIterator()->getArrayCopy();

        /** @var DeliveryMonitoringKeyValueTriplet $item */
        foreach ($copyArray as $key => $item){

            /** @var DeliveryMonitoringKeyValueTriplet $compareItem */
            foreach ($collection as $compareItem) {

                if ($item->equals($compareItem)) {
                    unset($copyArray[$key]);
                    break;
                }
            }
        }

        return new static($copyArray);
    }
}
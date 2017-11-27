<?php

namespace oat\taoProctoring\test\monitorCache\KeyValueDeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring\DeliveryMonitoringKeyValueTriplet;
use oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring\DeliveryMonitoringKeyValueTripletCollection;

class DeliveryMonitoringKeyValueTripletCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildCollection()
    {
        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $this->assertInstanceOf(DeliveryMonitoringKeyValueTripletCollection::class, $collection);
        $this->assertInstanceOf(DeliveryMonitoringKeyValueTriplet::class, $collection->first());
    }

    public function testToArray()
    {
        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $this->assertEquals([
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'key1',
                'monitoring_value' => 'value1',
            ],
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'key2',
                'monitoring_value' => 'value2',
            ],
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'key3',
                'monitoring_value' => 'value3',
            ]
        ], $collection->toArray());
    }

    public function testDiffToGetNewTriplets()
    {
        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'newKey' => 'newValue',
            'newKey1' => 'newValue1',
        ]);

        $existingCollection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $new = $collection->diffToGetNewTriplets($existingCollection);

        $this->assertEquals([
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'newKey',
                'monitoring_value' => 'newValue',
            ],
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'newKey1',
                'monitoring_value' => 'newValue1',
            ]
        ], $new->toArray());
    }

    public function testDiffToGetUpdatedTriplets()
    {
        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'updatedvalue1',
            'key2' => 'updatedvalue2',
            'key3' => 'value3',
        ]);

        $existingCollection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $new = $collection->diffToGetUpdatedTriplets($existingCollection);

        $this->assertEquals([
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'key1',
                'monitoring_value' => 'updatedvalue1',
            ],
            [
                'parent_id' => 'deliveryId',
                'monitoring_key' => 'key2',
                'monitoring_value' => 'updatedvalue2',
            ]
        ], $new->toArray());
    }

    public function testMarkAsUpdatedTripletsByKeys()
    {
        $collection = DeliveryMonitoringKeyValueTripletCollection::buildCollection('deliveryId', [
            'key1' => 'updatedvalue1',
            'key2' => 'updatedvalue2',
            'key3' => 'value3',
        ]);

        $idsToFilter = ['key1'];
        $collection->markAsUpdatedTripletsByKeys($idsToFilter);

        $collection = $collection->filter(function($entry) use ($idsToFilter) {
            return in_array($entry->getKey(), $idsToFilter);
        });
        /** @var DeliveryMonitoringKeyValueTriplet $item */
        $item = $collection->first();

        $this->assertTrue($item->isSaved());
    }
}

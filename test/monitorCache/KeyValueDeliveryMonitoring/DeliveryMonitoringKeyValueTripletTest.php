<?php

namespace oat\taoProctoring\test\monitorCache\KeyValueDeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\KeyValueDeliveryMonitoring\DeliveryMonitoringKeyValueTriplet;

class DeliveryMonitoringKeyValueTripletTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $kvTriplet = new DeliveryMonitoringKeyValueTriplet('deliveryId', 'key1', 'value1');
        $this->assertSame('deliveryId', $kvTriplet->getDeliveryId());
        $this->assertSame('key1', $kvTriplet->getKey());
        $this->assertSame('value1', $kvTriplet->getValue());
    }

    public function testEquals()
    {
        $kvTriplet = new DeliveryMonitoringKeyValueTriplet('deliveryId', 'key1', 'value1');
        $kvTriplet2 = new DeliveryMonitoringKeyValueTriplet('deliveryId', 'key1', 'value2');

        $this->assertTrue($kvTriplet->equals($kvTriplet));
        $this->assertFalse($kvTriplet->equals($kvTriplet2));
        $this->assertTrue($kvTriplet->hasSameKey($kvTriplet2));
        $this->assertFalse($kvTriplet->isSaved());

        $kvTriplet->setSaved(true);
        $this->assertTrue($kvTriplet->isSaved());
    }
}

<?php

namespace oat\taoProctoring\test\monitorCache\DeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoring\DeliveryMonitoringEntity;

class DeliveryMonitoringEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $entity = new DeliveryMonitoringEntity(12345, [
            'attr1' => 'value1',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ]);

        $this->assertSame(12345, $entity->getId());
        $this->assertEquals([
            'attr1' => 'value1',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ], $entity->getDataAttributes());
    }

    public function testShouldBeEqual()
    {
        $entity1 = new DeliveryMonitoringEntity(12345, [
            'attr1' => 'value1',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ]);

        $entity2 = new DeliveryMonitoringEntity(12345, [
            'attr1' => 'value1',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ]);

        $this->assertTrue($entity1->equals($entity2));
    }

    public function testShouldNotBeEqual()
    {
        $entity1 = new DeliveryMonitoringEntity(12345, [
            'attr1' => 'value1',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ]);

        $entity2 = new DeliveryMonitoringEntity(12345, [
            'attr1' => 'value111',
            'attr2' => 'value2',
            'attr3' => 'value3',
        ]);

        $this->assertFalse($entity1->equals($entity2));
    }
}

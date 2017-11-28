<?php

namespace oat\taoProctoring\test\monitorCache\DeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\DeliveryMonitoring\DeliveryMonitoringEntity;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoring\DeliveryMonitoringFactory;

class DeliveryMonitoringFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $factory = new DeliveryMonitoringFactory(['column1', 'column2', 'column3']);

        $entity = $factory->buildEntityFromRawArray([
            'delivery_execution_id' => 12345,
            'column1' => 'value1',
            'column2' => 'value2',
            'column3' => 'value3',
            'columnNotConfigured' => 'value8',
        ]);

        $this->assertInstanceOf(DeliveryMonitoringEntity::class, $entity);
        $this->assertEquals(12345, $entity->getId());
        $this->assertEquals([
            'column1' => 'value1',
            'column2' => 'value2',
            'column3' => 'value3',
        ], $entity->getDataAttributes());
    }
}

<?php
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
/**
 * Default monitoring cache service
 */
return new DeliveryMonitoringService(array(
    DeliveryMonitoringService::OPTION_PERSISTENCE => 'default'
));

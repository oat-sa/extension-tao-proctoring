<?php
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
/**
 * Default monitoring cache service
 */
return new MonitorCacheService(array(
    MonitorCacheService::OPTION_PERSISTENCE => 'default',
    MonitorCacheService::OPTION_PRIMARY_COLUMNS => [
        DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID,
        DeliveryMonitoringService::COLUMN_STATUS,
        DeliveryMonitoringService::COLUMN_CURRENT_ASSESSMENT_ITEM,
        DeliveryMonitoringService::COLUMN_TEST_TAKER,
        DeliveryMonitoringService::COLUMN_AUTHORIZED_BY,
        DeliveryMonitoringService::COLUMN_START_TIME,
        DeliveryMonitoringService::COLUMN_END_TIME,
    ]
));

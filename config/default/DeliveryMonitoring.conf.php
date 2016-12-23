<?php
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
/**
 * Default monitoring cache service
 */
return new MonitorCacheService(array(
    MonitorCacheService::OPTION_PERSISTENCE => 'default',
    MonitorCacheService::OPTION_PRIMARY_COLUMNS => [
        MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID,
        MonitoringStorage::COLUMN_STATUS,
        MonitoringStorage::COLUMN_CURRENT_ASSESSMENT_ITEM,
        MonitoringStorage::COLUMN_TEST_TAKER,
        MonitoringStorage::COLUMN_AUTHORIZED_BY,
        MonitoringStorage::COLUMN_START_TIME,
        MonitoringStorage::COLUMN_END_TIME,
    ]
));

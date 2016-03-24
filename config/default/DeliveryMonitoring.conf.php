<?php
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;
/**
 * Default monitoring cache service
 */
return new MonitorCacheService(array(
    MonitorCacheService::OPTION_PERSISTENCE => 'default'
));

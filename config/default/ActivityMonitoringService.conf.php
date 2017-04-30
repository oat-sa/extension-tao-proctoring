<?php
/**
 * Default config header
 *
 * To replace this add a file D:\domains\package-tao\taoProctoring\config/header/ActivityMonitoringService.conf.php
 */

use oat\taoProctoring\model\ActivityMonitoringService;

return new ActivityMonitoringService([
    ActivityMonitoringService::OPTION_ACTIVE_USER_THRESHOLD => 300,
    ActivityMonitoringService::OPTION_COMPLETED_ASSESSMENTS_AUTO_REFRESH => 30,
    ActivityMonitoringService::AUTO_REFRESH_INTERVAL => 60,
]);

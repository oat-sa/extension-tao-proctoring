<?php

/**
 * Default config header
 *
 * To replace this add a file
 * C:\domains\package-tao\taoProctoring\config/header/TestSessionConnectivityStatusService.conf.php
 */

use oat\taoProctoring\model\implementation\TestSessionConnectivityStatusService;

return new oat\taoProctoring\model\implementation\TestSessionConnectivityStatusService(
    [
        TestSessionConnectivityStatusService::HAS_ONLINE_MODE => false,
    ]
);

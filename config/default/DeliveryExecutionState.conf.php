<?php
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
/**
 * Default authorization service
 */
return new DeliveryExecutionStateService([
    'termination_delay_after_pause' => 'PT1H'
]);

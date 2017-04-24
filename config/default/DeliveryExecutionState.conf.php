<?php
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
/**
 * Default authorization service
 */
return new DeliveryExecutionStateService([
    DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE => 'PT1H',
    DeliveryExecutionStateService::OPTION_TIME_HANDLING => false,
    DeliveryExecutionStateService::OPTION_APPROXIMATE_TIMER => false,
    DeliveryExecutionStateService::OPTION_APPROXIMATE_TIMER_WARNING => 900,
]);

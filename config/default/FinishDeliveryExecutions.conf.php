<?php

return new \oat\taoProctoring\model\FinishDeliveryExecutionsService([
   \oat\taoProctoring\model\TerminateDeliveryExecutionsService::OPTION_TTL_AS_ACTIVE => 'PT6H',
   \oat\taoProctoring\model\TerminateDeliveryExecutionsService::OPTION_USE_DELIVERY_END_TIME => false
]);

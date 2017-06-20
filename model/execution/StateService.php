<?php

namespace oat\taoProctoring\model\execution;

use oat\taoDelivery\model\execution\StateService as DeliverStateService;

class StateService extends DeliverStateService
{
    public function getDeliveryStates()
    {
        return [
            DeliveryExecution::STATE_FINISHIED,
            DeliveryExecution::STATE_ACTIVE,
            DeliveryExecution::STATE_PAUSED,
            DeliveryExecution::STATE_TERMINATED,
        ];
    }
}

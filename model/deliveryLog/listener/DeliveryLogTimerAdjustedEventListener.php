<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */
declare(strict_types=1);

namespace oat\taoProctoring\model\deliveryLog\listener;

use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\event\DeliveryLogEvent;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;

class DeliveryLogTimerAdjustedEventListener extends ConfigurableService
{
    /**
     * @param DeliveryExecutionTimerAdjusted $event
     */
    public function onTimerAdjusted(DeliveryExecutionTimerAdjusted $event): void
    {
        $this->getDeliveryLogService()->log(
            $event->getDeliveryExecution()->getIdentifier(),
            DeliveryLogEvent::EVENT_ID_TEST_ADJUSTED_TIME,
            [
                'reason' => $event->getReason(),
                'increment' => $event->getSeconds(),
            ]
        );
    }

    /**
     * @return DeliveryLog|object
     */
    private function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }
}

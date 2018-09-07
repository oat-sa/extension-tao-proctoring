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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoProctoring\model;


use oat\taoProctoring\model\execution\DeliveryExecutionsUpdater;
use common_report_Report as Report;

class TerminateDeliveryExecutionsService extends DeliveryExecutionsUpdater
{
    const SERVICE_ID = 'taoProctoring/TerminateDeliveryExecutions';

    /**
     * Terminate delivery execution
     *
     * @param $deliveryExecution
     * @param $executionId
     * @param bool $isEndDate
     * @return mixed|void
     * @throws \common_exception_Error
     */
    protected function action($deliveryExecution, $executionId, $isEndDate = false)
    {
        $this->getDeliveryStateService()->terminateExecution($deliveryExecution, [
            'reasons' =>[
                'category' => 'Technical'
            ],
            'comment' => $isEndDate
                ? 'The assessment was automatically terminated because end time expired.'
                : 'The assessment was automatically terminated.'
        ]);
        $this->report->add(Report::createSuccess('Execution terminated with success:'. $executionId ));
    }
}
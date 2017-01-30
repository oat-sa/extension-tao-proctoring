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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\execution\DeliveryExecution;

/**
 * Class Tools
 *
 * @package oat\taoProctoring\controller
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class Tools extends SimplePageModule
{

    use OntologyAwareTrait;

    /**
     * Action pauses all the active delivery executions
     */
    public function pauseActiveExecutions()
    {
        if ($this->isRequestPost()) {
            $reason = __('Pause due to server maintenance');
            $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
            $deliveryExecutions = $monitoringService->find(
                [DeliveryMonitoringService::STATUS => DeliveryExecution::STATE_ACTIVE],
                ['asArray' => true]
            );
            $ids = array_map(function ($deliveryExecution) {
                return $deliveryExecution['delivery_execution_id'];
            }, $deliveryExecutions);
            $paused = DeliveryHelper::pauseExecutions($ids, $reason);
            $notPaused = array_diff($ids, $paused);

            $this->returnJson([
                'success' => !count($notPaused),
                'message' => count($paused) . ' ' . __('sessions paused'),
                'processed' => $paused,
                'unprocessed' => $notPaused
            ]);
        } else {
            $this->setView('Tools/pause_active_executions.tpl');
        }
    }
}
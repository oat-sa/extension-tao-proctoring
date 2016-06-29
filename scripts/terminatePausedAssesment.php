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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\DeliveryExecutionStateService;

/**
 * Script that terminates assessments, paused longer than XXX
 */

common_Logger::d('Termination expired paused execution started at ' . date(DATE_RFC3339));

require_once __DIR__ . '/../../tao/includes/raw_start.php';

common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

$deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
$deliveryExecutionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();

$deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
$deliveries = $deliveryClass->getInstances(true);


foreach ($deliveries as $delivery) {
    if ($delivery->exists()) {

        $deliveryExecutions = $deliveryExecutionService->getExecutionsByDelivery($delivery);

        foreach ($deliveryExecutions as $deliveryExecution) {
            $executionState = $deliveryExecutionStateService->getState($deliveryExecution);

            if (DeliveryExecution::STATE_PAUSED === $executionState && TestSessionService::singleton()->isExpired($deliveryExecution)) {
                $deliveryExecutionStateService->terminateExecution(
                    $deliveryExecution,
                    ['reasons' => 'Paused delivery execution was expired', 'comment' => '']
                );
            }
        }
        common_Logger::d('Checked ' . $delivery->getLabel() . ' with ' . count($deliveryExecutions) . ' corresponding executions');
    }
}

common_Logger::d('Termination expired paused execution finished at ' . date(DATE_RFC3339));

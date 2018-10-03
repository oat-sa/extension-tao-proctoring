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

namespace oat\taoProctoring\scripts;

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use common_Logger;
use common_report_Report as Report;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionState;
use DateTimeImmutable;
use DateInterval;

/**
 * Script that terminates assessments, which are in awaiting state longer than XXX
 * Run example: `sudo php index.php 'oat\taoProctoring\scripts\TerminateNotStartedAssessment'`
 */
class TerminateNotStartedAssessment extends AbstractExpiredSessionSeeker
{
    use ServiceLocatorAwareTrait;

    /**
     * @var \core_kernel_classes_Resource
     */
    protected $report;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param array $params
     * @return Report
     */
    public function __invoke($params)
    {
        $this->params = $params;

        $this->report = new Report(
            Report::TYPE_INFO,
            'Cancellation of expired delivery executions...'
        );
        common_Logger::d('Cancellation of expired delivery executions started at ' . date(DATE_RFC3339));

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoQtiTest');

        $deliveryExecutionService = ServiceProxy::singleton();

        $cancel = 0;
        $pause = 0;

        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $deliveryExecutionsData = $deliveryMonitoringService->find([
            DeliveryMonitoringService::STATUS => [DeliveryExecutionState::STATE_AUTHORIZED, DeliveryExecutionState::STATE_AWAITING],
        ]);

        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        foreach ($deliveryExecutionsData as $deliveryExecutionData) {
            try {
                $data = $deliveryExecutionData->get();
                $deliveryExecution = $deliveryExecutionService->getDeliveryExecution(
                    $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
                );

                if ($this->isExpired($deliveryExecution)) {
                    if ($deliveryExecutionStateService->isCancelable($deliveryExecution)){
                        $deliveryExecutionStateService->cancelExecution($deliveryExecution, [
                            'reasons' => ['category' => 'Examinee', 'subCategory' => 'Authorization'],
                            'comment' => __('Automatically reset by the system due to authorized test not being launched by test taker.'),
                        ]);
                        $cancel++;
                    } else {
                        $deliveryExecutionStateService->pauseExecution($deliveryExecution, [
                            'reasons' => ['category' => 'Examinee', 'subCategory' => 'Authorization'],
                            'comment' => __('Automatically paused by the system due to authorized test not being launched by test taker.'),
                        ]);
                        $pause++;
                    }

                }
            } catch (\Exception $e) {
                $this->addReport(Report::TYPE_ERROR, $e->getMessage());
            }
        }

        $msg = ($cancel > 0 ? "{$cancel} executions has been canceled. " : "") . ($pause > 0 ? "{$pause} executions has been paused." : "");
        $msg = !$msg ? "Expired executions not found." : $msg;

        $this->addReport(Report::TYPE_INFO, $msg);

        common_Logger::d('Cancellation of expired delivery executions finished at ' . date(DATE_RFC3339));

        return $this->report;
    }

    /**
     * Checks if delivery execution was abandoned after authorization
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws
     */
    public function isExpired(DeliveryExecution $deliveryExecution)
    {
        $result = false;
        $executionState = $deliveryExecution->getState()->getUri();
        $lastTestTakersEvent = $this->getLastTestTakersEvent($deliveryExecution);

        if (in_array($executionState, [
                DeliveryExecutionState::STATE_AWAITING,
                DeliveryExecutionState::STATE_AUTHORIZED,
            ]) &&
            $lastTestTakersEvent)
        {
            /** @var \oat\taoProctoring\model\implementation\DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
            $delay = $deliveryExecutionStateService->getOption(DeliveryExecutionStateService::OPTION_CANCELLATION_DELAY);
            $lastTestEventTime = (new DateTimeImmutable())->setTimestamp($lastTestTakersEvent['created_at']);
            $result = ($lastTestEventTime->add(new DateInterval($delay)) < (new DateTimeImmutable()));
        }

        return $result;
    }
}

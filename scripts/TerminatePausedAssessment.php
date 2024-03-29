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
use oat\taoProctoring\model\deliveryLog\event\DeliveryLogEvent;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use common_Logger;
use common_report_Report as Report;
use oat\taoDelivery\model\execution\DeliveryExecution;
use DateTimeImmutable;
use DateInterval;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionState;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * Script that terminates assessments, paused longer than XXX.
 *
 * Parameter 1 - Wet Run (optional) - A boolean value (0|1) to indicate wheter or not making a dry run. Default is 1
 *                                    for backward compatibility reasons.
 * Parameter 2 - Max Terminations (optional) - An integer value to indicate the maximum number of assessments to be
 *                                             terminated. Default is 0, meaning no limit.
 *
 * Run examples:
 *
 * # Wet run with no max terminations.
 * sudo php index.php 'oat\taoProctoring\scripts\TerminatePausedAssessment'
 *
 * # Wet run with 5 terminations maximum.
 * sudo php index.php 'oat\taoProctoring\scripts\TerminatePausedAssessment' 1 5
 *
 * # Dry run with no max terminations.
 * sudo php index.php 'oat\taoProctoring\scripts\TerminatePausedAssessment' 0
 */
class TerminatePausedAssessment extends AbstractExpiredSessionSeeker
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Report
     */
    protected $report;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var boolean
     */
    protected $wetRun = true;

    /**
     * @var integer
     */
    protected $maxTerminate = 0;

    /**
     * @param array $params
     * @return Report
     * @throws
     */
    public function __invoke($params)
    {
        $this->params = $params;

        // Should we make a wet run?
        if (isset($this->params[0])) {
            $this->wetRun = (boolval($this->params[0]) === true);

            // Should we limit the number of tests being terminated?
            if (isset($this->params[1])) {
                $this->maxTerminate = intval($this->params[1]);
            }
        }
        $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
        $hasOptionTerminationDelayAfterPause = $deliveryExecutionStateService->hasOption(
            DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE
        );

        if (!$hasOptionTerminationDelayAfterPause) {
            return new Report(
                Report::TYPE_WARNING,
                'Option `termination_delay_after_pause` not configured. `TerminatePausedAssessment` execution '
                    . 'terminated'
            );
        }
        $wetInfo = ($this->wetRun === false) ? 'dry' : 'wet';
        $this->report = new Report(
            Report::TYPE_INFO,
            "Automatic termination of expired executions (${wetInfo} run)..."
        );
        common_Logger::d('Termination expired paused execution started at ' . date(DATE_RFC3339));

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoQtiTest');

        $deliveryExecutionService = ServiceProxy::singleton();

        $count = 0;
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $delay = $deliveryExecutionStateService->getOption(
            DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE
        );
        $startTimeFilter = (new DateTimeImmutable('now'))->sub(new DateInterval($delay))->getTimestamp();
        $deliveryExecutionsData = $deliveryMonitoringService->find([
            [DeliveryMonitoringService::STATUS     => [
                DeliveryExecution::STATE_ACTIVE,
                DeliveryExecution::STATE_PAUSED
            ]],
            'AND',
            [DeliveryMonitoringService::START_TIME => '<' . $startTimeFilter]
        ]);
        foreach ($deliveryExecutionsData as $deliveryExecutionData) {
            $data = $deliveryExecutionData->get();
            $deliveryExecution = $deliveryExecutionService->getDeliveryExecution(
                $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
            );
            try {
                if ($this->isExpired($deliveryExecution)) {
                    $this->terminateExecution($deliveryExecution);
                    $count++;
                }
            } catch (\common_exception_NotFound $e) {
                //Delivery execution entry missed.
                $deliveryExecutionData->update(
                    DeliveryMonitoringService::STATUS,
                    DeliveryExecution::STATE_TERMINATED
                );
                $deliveryMonitoringService->partialSave($deliveryExecutionData);
                common_Logger::w(
                    'Delivery execution ' . $deliveryExecution->getIdentifier() .
                    ' is missed. Set it\'s state in delivery monitoring to Terminated'
                );
                $this->addReport(
                    Report::TYPE_WARNING,
                    'Delivery execution {$deliveryExecution->getIdentifier()} state in delivery monitoring was '
                        . 'set to `terminated` due to missed delivery execution entry.'
                );
            } catch (\Exception $e) {
                $this->addReport(Report::TYPE_ERROR, $e->getMessage());
            } catch (\Throwable $e) {
                common_Logger::f($e->getMessage());
            }

            // Should we stop terminating assessments?
            if ($this->maxTerminate > 0 && $count >= $this->maxTerminate) {
                break;
            }
        }

        $msg = $count > 0 ? "{$count} executions has been terminated." : "Expired executions not found.";
        $this->addReport(Report::TYPE_INFO, $msg);

        common_Logger::d('Termination expired paused execution finished at ' . date(DATE_RFC3339));

        return $this->report;
    }

    /**
     * $terminate delivery execution
     * @param DeliveryExecution $deliveryExecution
     */
    protected function terminateExecution(DeliveryExecution $deliveryExecution)
    {
        if ($this->wetRun === true) {
            $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(
                DeliveryExecutionStateService::SERVICE_ID
            );
            $deliveryExecutionStateService->terminateExecution(
                $deliveryExecution,
                [
                    'reasons' => $this->getTerminationReasons(),
                    'comment' => __('The assessment was automatically terminated by the system due to inactivity.'),
                ]
            );
            $this->addReport(
                Report::TYPE_INFO,
                "Delivery execution {$deliveryExecution->getIdentifier()} has been terminated."
            );
        } else {
            $this->addReport(
                Report::TYPE_INFO,
                "Delivery execution {$deliveryExecution->getIdentifier()} should be terminated."
            );
        }
    }

    /**
     * Return Termination Reasons.
     *
     * Provides the 'reasons' information array with keys 'category' and 'subCategory'.
     * This method may be overriden by subclasses to provide customer specific information.
     *
     * @return array.
     */
    protected function getTerminationReasons()
    {
        // @fixme remove customer specific information.
        return ['category' => 'Technical', 'subCategory' => 'ACT'];
    }

    /**
     * Checks if delivery execution was expired after pausing
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws
     */
    protected function isExpired(DeliveryExecution $deliveryExecution)
    {
        $result = false;
        $executionState = $deliveryExecution->getState()->getUri();

        if (
            in_array($executionState, [
                DeliveryExecutionState::STATE_PAUSED,
                DeliveryExecutionState::STATE_ACTIVE,
            ], true)
        ) {
            /** @var \oat\taoProctoring\model\implementation\DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);

            if ($executionState === DeliveryExecutionState::STATE_ACTIVE) {
                $lastTestTakersEvent = $this->getLastTestTakersEvent($deliveryExecution);
                $lastEventTime = (new DateTimeImmutable())->setTimestamp($lastTestTakersEvent['created_at']);
            } else {
                $lastEventTime = $this->getLastPause($deliveryExecution);
            }

            if ($lastEventTime) {
                $delay = $deliveryExecutionStateService->getOption(
                    DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE
                );
                $result = ($lastEventTime->add(new DateInterval($delay)) < (new DateTimeImmutable('now')));
            }
        }

        return $result;
    }

    /**
     * Get time of the last pause
     * @param DeliveryExecution $deliveryExecution
     * @return DateTimeImmutable
     * @throws \Exception
     */
    private function getLastPause(DeliveryExecution $deliveryExecution)
    {
        $deliveryLogService = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $pauses = array_reverse(
            $deliveryLogService->get(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_PAUSE
            )
        );

        if (!empty($pauses)) {
            $lastPause = $pauses[0]['created_at'];
        } else {
            $lastPause = $this->getLastTestTakersEvent($deliveryExecution)['created_at'];
        }

        return (new DateTimeImmutable())->setTimestamp($lastPause);
    }
}

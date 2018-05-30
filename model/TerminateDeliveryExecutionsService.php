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

use core_kernel_classes_Property;
use DateInterval;
use DateTimeImmutable;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use common_report_Report as Report;

class TerminateDeliveryExecutionsService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/TerminateDeliveryExecutions';

    const OPTION_TTL_AS_ACTIVE = 'ttlAsActive';

    const OPTION_USE_DELIVERY_END_TIME = 'useDeliveryEndTime';

    /** @var Report */
    protected $report;

    /**
     * @throws \common_exception_Error
     */
    public function execute()
    {
        $deliveryMonitoringService = $this->getDeliveryMonitoringService();
        $executionsMonitoringData  = $deliveryMonitoringService->find([
            DeliveryMonitoringService::STATUS => [
                DeliveryExecution::STATE_ACTIVE,
                DeliveryExecution::STATE_PAUSED,
            ]
        ]);
        $this->report = Report::createInfo('Terminating executions...');
        $count  = 0;

        foreach ($executionsMonitoringData as $datum) {
            $data        = $datum->get();
            $executionId = $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID];

            if ($this->isBasedOnEndDateTime()){
                $deliveryID  = $data[DeliveryMonitoringService::DELIVERY_ID];
                $endDateTime = $this->getDeliveryEndDateTime($deliveryID);
                //if no end date time found, fallback to normal terminating execution based on TTL.
                if (!is_null($endDateTime)){
                    $terminated = $this->terminateDeliveryBasedOnEndDate($endDateTime, $executionId);
                    if($terminated){
                        $count++;
                    }
                    continue;
                }

            }

            $terminated = $this->terminateExecutionsBasedOnTTL($executionId);
            if($terminated){
                $count++;
            }
        }

        $this->report->add(Report::createInfo('Number of executions terminated: '. $count));

        return $this->report;
    }

    /**
     * @param $executionId
     * @return bool
     * @throws \common_exception_Error
     */
    protected function terminateExecutionsBasedOnTTL($executionId)
    {
        try {
            $deliveryExecution = $this->getServiceProxy()->getDeliveryExecution($executionId);
            $lastInteraction   = $this->getLastInteractionDateTime($deliveryExecution);

            if ($lastInteraction === null){
                $this->report->add(Report::createFailure('Execution last interaction cannot be found: '. $executionId));
                return false;
            }

            $ttl = $this->getTtlAsActive();
            if ($ttl === null) {
                $this->report->add(Report::createFailure('Execution ttl not set: '. $executionId));
                return false;
            }

            $timeUntilToLive = clone $lastInteraction;
            $timeUntilToLive = $timeUntilToLive->add(new DateInterval($ttl));

            if ((new DateTimeImmutable('now')) >= $timeUntilToLive) {
                $this->getDeliveryStateService()->terminateExecution($deliveryExecution,[
                    'reasons' =>[
                        'category' => 'Technical'
                    ],
                    'comment' => 'The assessment was automatically terminated.'
                ]);
                $this->report->add(Report::createSuccess('Execution terminated with success:'. $executionId ));

                return true;
            } else {
                $this->report->add(Report::createInfo('Execution not expired yet:'. $executionId .
                    ' Last Interaction:'.$lastInteraction->format('Y-m-d H:i:s') .
                    ' Time when will expire:'.$timeUntilToLive->format('Y-m-d H:i:s')
                ));
            }

        } catch (\common_exception_NotFound $e) {
            $this->report->add(Report::createFailure('Execution cannot be found: '. $executionId));
            $this->report->add(Report::createFailure($e->getMessage()));

        }

        return false;
    }

    /**
     * @param DateTimeImmutable $endDateTime
     * @param $executionId
     * @return bool
     * @throws \common_exception_Error
     */
    protected function terminateDeliveryBasedOnEndDate(DateTimeImmutable $endDateTime, $executionId)
    {
        try {
            $deliveryExecution = $this->getServiceProxy()->getDeliveryExecution($executionId);
            $lastInteraction   = $this->getLastInteractionDateTime($deliveryExecution);

            if ($lastInteraction === null){
                $this->report->add(Report::createFailure('Execution last interaction cannot be found: '. $executionId));
                return false;
            }

            if ((new DateTimeImmutable('now')) >= $endDateTime) {
                $this->getDeliveryStateService()->terminateExecution($deliveryExecution,[
                    'reasons' =>[
                        'category' => 'Technical'
                    ],
                    'comment' => 'The assessment was automatically terminated because end time expired.'
                ]);
                $this->report->add(Report::createSuccess('Execution terminated with success:'. $executionId ));

                return true;
            } else {
                $this->report->add(Report::createInfo('Execution not expired yet:'. $executionId .
                    ' Last Interaction:'.$lastInteraction->format('Y-m-d H:i:s') .
                    ' Time when will expire:'.$endDateTime->format('Y-m-d H:i:s')
                ));
            }

        } catch (\common_exception_NotFound $e) {
            $this->report->add(Report::createFailure('Execution cannot be found: '. $executionId));
            $this->report->add(Report::createFailure($e->getMessage()));
        }

        return false;
    }
    /**
     * @return array|DeliveryExecutionStateService|object
     */
    protected function getDeliveryStateService()
    {
        /** @var DeliveryExecutionStateService $service */
        $service = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);

        return $service;
    }

    /**
     * @return array|DeliveryMonitoringService|object
     */
    protected function getDeliveryMonitoringService()
    {
        /** @var DeliveryMonitoringService $service */
        $service = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        return $service;
    }

    /**
     * @return array|ServiceProxy|object
     */
    protected function getServiceProxy()
    {
        /** @var ServiceProxy $service */
        $service = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);

        return $service;
    }

    /**
     * @return array|DeliveryLog|object
     */
    protected function getDeliveryLog()
    {
        /** @var DeliveryLog $deliveryLogService */
        $deliveryLogService = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);

        return $deliveryLogService;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return bool|DateTimeImmutable|null
     * @throws \common_exception_NotFound
     */
    protected function getLastInteractionDateTime(DeliveryExecution $deliveryExecution)
    {
        $deliveryLogService  = $this->getDeliveryLog();
        $testTakerIdentifier = $deliveryExecution->getUserIdentifier();

        $events = array_reverse($deliveryLogService->get($deliveryExecution->getIdentifier()));

        $lastTestTakersEvent = null;
        foreach ($events as $event) {
            if ($event[DeliveryLog::CREATED_BY] === $testTakerIdentifier) {
                $lastTestTakersEvent = $event;
                break;
            }
        }

        if (!is_null($lastTestTakersEvent)){
            $lastEventTime = (new DateTimeImmutable())->setTimestamp($lastTestTakersEvent[DeliveryLog::CREATED_AT]);
        } else {
            return null;
        }

        return $lastEventTime;
    }

    /**
     * @return string|null
     */
    protected function getTtlAsActive()
    {
        return $this->getOption(static::OPTION_TTL_AS_ACTIVE);
    }

    /**
     * @return string|null
     */
    protected function isBasedOnEndDateTime()
    {
        return (bool)$this->getOption(static::OPTION_USE_DELIVERY_END_TIME);
    }

    /**
     * @param $deliveryId
     * @return null|DateTimeImmutable
     */
    protected function getDeliveryEndDateTime($deliveryId)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryId);
        try {
            $endDate = (string)$delivery->getUniquePropertyValue(
                new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_END)
            );

            return (new DateTimeImmutable)->setTimestamp($endDate);

        } catch (\Exception $e) {
            return null;
        }
    }
}
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

use DateInterval;
use DateTimeImmutable;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use common_report_Report as Report;

class FinishDeliveryExecutionsService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/FinishDeliveryExecutions';

    const OPTION_TTL_AS_ACTIVE = 'ttlAsActive';

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
        $report = Report::createInfo('Finishing executions...');
        $count  = 0;

        foreach ($executionsMonitoringData as $datum) {
            $data        = $datum->get();
            $executionId = $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID];
            try {
                $deliveryExecution = $this->getServiceProxy()->getDeliveryExecution($executionId);
                $lastInteraction   = $this->getLastInteractionDateTime($deliveryExecution);
                if ($lastInteraction === null){
                    $report->add(Report::createFailure('Execution last interaction cannot be found: '. $executionId));
                    continue;
                }

                $ttl = $this->getTtlAsActive();
                if ($ttl === null) {
                    $report->add(Report::createFailure('Execution ttl not set: '. $executionId));
                    continue;
                }

                $timeUntilToLive = clone $lastInteraction;
                $timeUntilToLive = $timeUntilToLive->add(new DateInterval($ttl));

                if ((new DateTimeImmutable('now')) >= $timeUntilToLive) {
                    $this->getDeliveryStateService()->finishExecution($deliveryExecution,[
                        'reasons' =>[
                            'category' => 'Technical'
                        ],
                        'comment' => 'The assessment was automatically finished.'
                    ]);
                    $report->add(Report::createSuccess('Execution finished with success:'. $executionId ));
                    $count++;
                } else {
                    $report->add(Report::createInfo('Execution not expired yet:'. $executionId .
                        ' Last Interaction:'.$lastInteraction->format('Y-m-d H:i:s') .
                        ' Time when will expire:'.$timeUntilToLive->format('Y-m-d H:i:s')
                    ));
                }

            } catch (\common_exception_NotFound $e) {
                $report->add(Report::createFailure('Execution cannot be found: '. $executionId));
                $report->add(Report::createFailure($e->getMessage()));
            }
        }

        $report->add(Report::createInfo('Number of executions finished: '. $count));

        return $report;
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
}
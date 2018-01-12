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
use oat\taoProctoring\model\ActivityMonitoringService;
use oat\taoProctoring\model\datatable\DeliveriesActivityDatatable;
use oat\taoProctoring\model\event\DeliveryExecutionFinished;

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
     * Show assessment activity dashboard
     */
    public function assessmentActivity()
    {
        $service = $this->getServiceLocator()->get(ActivityMonitoringService::SERVICE_ID);

        // Reason categories
        $this->setData('reason_categories', DeliveryHelper::getAllReasonsCategories());

        // Config
        $config = [ActivityMonitoringService::OPTION_USER_ACTIVITY_WIDGETS =>
            $service->getOption(ActivityMonitoringService::OPTION_USER_ACTIVITY_WIDGETS),
            ActivityMonitoringService::OPTION_ASSESSMENT_ACTIVITY_AUTO_REFRESH =>
                $service->getOption(ActivityMonitoringService::OPTION_ASSESSMENT_ACTIVITY_AUTO_REFRESH),
            ActivityMonitoringService::OPTION_COMPLETED_ASSESSMENTS_AUTO_REFRESH =>
                $service->getOption(ActivityMonitoringService::OPTION_COMPLETED_ASSESSMENTS_AUTO_REFRESH),
        ];
        $this->setData('config', $config);

        $this->setView('Tools/assessment_activity.tpl');
    }

    /**
     * Show assessment activity data as json
     */
    public function assessmentActivityData()
    {
        $service = $this->getServiceLocator()->get(ActivityMonitoringService::SERVICE_ID);
        $data = $service->getData();

        $this->returnJson([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get completed assessments data
     */
    public function completedAssessmentsData()
    {
        $timePeriod = $this->getRequestParameter('interval');

        $eventLog = $this->getServiceLocator()->get(\oat\taoEventLog\model\eventLog\LoggerService::SERVICE_ID);

        $tz = new \DateTimeZone(\common_session_SessionManager::getSession()->getTimeZone());
        $timeKeys = $this->getTimeKeys($timePeriod);
        $interval = $this->getInterval($timePeriod);

        foreach ($timeKeys as $timeKey) {
            $to = clone($timeKey);
            $from = clone($to);
            $from->sub($interval);
            $countEvents = $eventLog->count([
                ['occurred', 'between', $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')],
                ['event_name', '=', DeliveryExecutionFinished::class],
            ]);
            $result['time'][] = $to->setTimezone($tz)->format('Y-m-d H:i:s');
            $result['amount'][] = $countEvents;
        }

        $this->returnJson($result, 200);
    }

    /**
     *
     */
    public function deliveriesActivityData()
    {
        $this->returnJson(new DeliveriesActivityDatatable());
    }

    /**
     * Action pauses all the active delivery executions
     */
    public function pauseActiveExecutions()
    {
        if (!$this->isRequestPost()) {
            throw new \common_exception_BadRequest('Invalid request. Only POST method allowed.');
        }

        $reason = $this->hasRequestParameter('reason') ? $this->getRequestParameter('reason') : [
            'reasons' => ['category' => 'Technical', 'subCategory' => 'ACT'],
            'comment' => __('Pause due to server maintenance'),
        ];
        /** @var DeliveryMonitoringService $monitoringService */
        $monitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
        $deliveryExecutions = $monitoringService->find(
            [DeliveryMonitoringService::STATUS => DeliveryExecution::STATE_ACTIVE],
            ['asArray' => true]
        );
        $ids = array_map(function ($deliveryExecution) {
            return $deliveryExecution['delivery_execution_id'];
        }, $deliveryExecutions);
        $stats = DeliveryHelper::pauseExecutions($ids, $reason);
        $paused = $stats['processed'];
        $notPaused = $stats['unprocessed'];

        $this->returnJson([
            'success' => true,
            'data' => [
                'message' => count($paused) . ' ' . __('sessions paused'),
                'processed' => $paused,
                'unprocessed' => $notPaused
            ]
        ]);
    }

    /**
     * @return \DateInterval
     */
    private function getInterval($timePeriod)
    {
        $interval = new \DateInterval('PT1H');

        if ($timePeriod) {
            switch ($timePeriod) {
                case 'day':
                    $interval = new \DateInterval('PT1H');
                    break;
                case 'week':
                    $interval = new \DateInterval('P1D');
                    break;
                case 'month':
                    $interval = new \DateInterval('P1D');
                    break;
                case 'prevmonth':
                    $interval = new \DateInterval('P1D');
                    break;
                default:
                    $interval = new \DateInterval('PT1H');
                    break;
            }
        }

        return $interval;
    }

    /**
     * @return \DateTime[]
     */
    private function getTimeKeys($timePeriod)
    {
        /** @var ActivityMonitoringService $service */
        $service = $this->getServiceLocator()->get(ActivityMonitoringService::SERVICE_ID);

        $amount = null;
        $startDate = null;

        if ($timePeriod) {
            switch ($timePeriod) {
                case 'day':
                    $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    break;
                case 'week':
                    $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    $amount = 7;
                    break;
                case 'month':
                    $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    $amount = cal_days_in_month(CAL_GREGORIAN, $startDate->format('m'), $startDate->format('Y'));
                    break;
                case 'prevmonth':
                    $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    $startDate->sub(new \DateInterval('P'.cal_days_in_month(CAL_GREGORIAN, $startDate->format('m'), $startDate->format('Y')).'D'));
                    $amount = cal_days_in_month(CAL_GREGORIAN, $startDate->format('m'), $startDate->format('Y'));
                    break;
                default:
                    $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    break;
            }
        }

        return $service->getTimeKeys($this->getInterval($timePeriod), $startDate, $amount);
    }
}
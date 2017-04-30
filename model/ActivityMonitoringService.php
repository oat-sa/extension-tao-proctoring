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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\model;

use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoEventLog\model\requestLog\RequestLogStorage;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * Service to manage and monitor assessment activity
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ActivityMonitoringService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/ActivityMonitoringService';

    /** Threshold in seconds */
    const OPTION_ACTIVE_USER_THRESHOLD = 'active_user_threshold';

    /** Interval of refreshing assessment activity graph in seconds. 0 - no auto refresh */
    const OPTION_COMPLETED_ASSESSMENTS_AUTO_REFRESH = 'completed_assessments_auto_refresh';

    /** Interval of refreshing assessment activity data in seconds. 0 - no auto refresh */
    const AUTO_REFRESH_INTERVAL = 'auto_refresh_interval';

    /**
     * @var array list of all the statuses uris
     */
    protected $deliveryStatuses = [
        DeliveryExecution::STATE_AWAITING,
        DeliveryExecution::STATE_AUTHORIZED,
        DeliveryExecution::STATE_PAUSED,
        DeliveryExecution::STATE_ACTIVE,
        DeliveryExecution::STATE_TERMINATED,
        DeliveryExecution::STATE_CANCELED,
        DeliveryExecution::STATE_FINISHIED,
    ];

    /**
     * ActivityMonitoringService constructor.
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $deliveryStatuses = [];
        foreach ($this->deliveryStatuses as $deliveryStatus) {
            $deliveryStatuses[] = new \core_kernel_classes_Resource($deliveryStatus);
        }
        $this->deliveryStatuses = $deliveryStatuses;
    }

    /**
     * Return comprehensive activity monitoring data.
     * @return array
     */
    public function getData()
    {
        $awaiting = $this->getNumberOfAssessments(DeliveryExecution::STATE_AWAITING);
        $authorized = $this->getNumberOfAssessments(DeliveryExecution::STATE_AUTHORIZED);
        $paused = $this->getNumberOfAssessments(DeliveryExecution::STATE_PAUSED);
        $active = $this->getNumberOfAssessments(DeliveryExecution::STATE_ACTIVE);
        $current = $awaiting + $authorized + $paused + $active;

        return [
            'active_proctors' => $this->getNumberOfActiveUsers(ProctorService::ROLE_PROCTOR),
            'active_test_takers' => $this->getNumberOfActiveUsers(INSTANCE_ROLE_DELIVERY),
            'total_assessments' => $this->getNumberOfAssessments(),
            'total_current_assessments' => $current,
            'awaiting_assessments' => $awaiting,
            'authorized_but_not_started_assessments' => $authorized,
            'paused_assessments' => $paused,
            'in_progress_assessments' => $active,
            'deliveries_statistics' => $this->getStatesByDelivery(),
        ];
    }

    /**
     * Get array of DateTime objects build from $date (or current time if not given) $amount times back with given interval
     * Example:
     * $timeKeys = $service->getTimeKeys(new \DateInterval('PT1H'), new \DateTime('now'), 24);
     *
     *   array (
     *     0 =>
     *       DateTime::__set_state(array(
     *       'date' => '2017-04-24 08:00:00.000000',
     *       'timezone_type' => 1,
     *       'timezone' => '+00:00',
     *     )),
     *     1 =>
     *       DateTime::__set_state(array(
     *       'date' => '2017-04-24 07:00:00.000000',
     *       'timezone_type' => 1,
     *       'timezone' => '+00:00',
     *     )),
     *     2 =>
     *       DateTime::__set_state(array(
     *       'date' => '2017-04-24 06:00:00.000000',
     *       'timezone_type' => 1,
     *       'timezone' => '+00:00',
     *     )),
     *       ...
     *   )
     *
     * @param \DateInterval $interval
     * @param \DateTime|null $date
     * @param null $amount
     * @return \DateTime[]
     */
    public function getTimeKeys(\DateInterval $interval, \DateTime $date = null, $amount = null)
    {
        $timeKeys = [];
        if ($date === null) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if ($interval->format('%i') > 0) {
            $date->setTime($date->format('H'), $date->format('i')+1, 0);
            $amount = $amount === null ? 60 : $amount;
        }
        if ($interval->format('%h') > 0) {
            $date->setTime($date->format('H')+1, 0, 0);
            $amount = $amount === null ? 24 : $amount;
        }
        if ($interval->format('%d') > 0) {
            $date->setTime(0, 0, 0);
            $date->setDate($date->format('Y'), $date->format('m'), $date->format('d')+1);
            $amount = $amount === null ? cal_days_in_month(CAL_GREGORIAN, $date->format('m'), $date->format('Y')) : $amount;
        }
        if ($interval->format('%m') > 0) {
            $date->setTime(0, 0, 0);
            $date->setDate($date->format('Y'), $date->format('m')+1, 1);
            $amount = $amount === null ? 12 : $amount;
        }

        while ($amount > 0) {
            $timeKeys[] = new \DateTime($date->format(\DateTime::ISO8601), new \DateTimeZone('UTC'));
            $date->sub($interval);
            $amount--;
        }
        return $timeKeys;
    }

    /**
     * @param null|string $state
     * @return int
     */
    protected function getNumberOfAssessments($state = null)
    {
        $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        if ($state === null) {
            return $deliveryMonitoringService->count();
        } else {
            return $deliveryMonitoringService->count([DeliveryMonitoringService::STATUS => $state]);
        }
    }

    /**
     * @param null|string $role
     * @return int
     */
    protected function getNumberOfActiveUsers($role = null)
    {
        /** @var  RequestLogStorage $requestLogService */
        $requestLogService = $this->getServiceManager()->get(RequestLogStorage::SERVICE_ID);
        $now = microtime(true);
        $filter = [
            [RequestLogStorage::EVENT_TIME, 'between', $now - $this->getOption(self::OPTION_ACTIVE_USER_THRESHOLD), $now]
        ];
        if ($role !== null) {
            $filter[] = [RequestLogStorage::USER_ROLES, 'like', '%,' . $role . ',%'];
        }
        return $requestLogService->count($filter, ['group'=>RequestLogStorage::USER_ID]);
    }

    /**
     * Get list of all the deliveries and number of it's executions in each status
     * Result indexed by delivery Uri
     * @return array
     */
    protected function getStatesByDelivery()
    {
        $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $deliveries = DeliveryAssemblyService::singleton()->getAllAssemblies();
        $result = [];
        foreach ($deliveries as $delivery) {
            $deliveryData = [];
            foreach ($this->deliveryStatuses as $deliveryStatus) {
                $deliveryData[$deliveryStatus->getLabel()] = $deliveryMonitoringService->count([
                    [DeliveryMonitoringService::STATUS => $deliveryStatus->getUri()],
                    'AND',
                    ['delivery_id' => $delivery->getUri()]
                ]);
            }
            $deliveryData['label'] = $delivery->getLabel();
            $result[$delivery->getUri()] = $deliveryData;

        }
        return $result;
    }
}

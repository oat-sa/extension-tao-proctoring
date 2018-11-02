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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoProctoring\model\implementation;

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\TestSessionHistoryService as TestSessionHistoryServiceInterface;
use \oat\oatbox\service\ConfigurableService;
use DateTime;
use tao_helpers_Date as DateHelper;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\tao\helpers\UserHelper;

/**
 * Service is used to retrieve test session history
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package oat\taoProctoring
 */
class TestSessionHistoryService extends ConfigurableService implements TestSessionHistoryServiceInterface
{
    /**
     * List of event ids which should be excluded from history (in lower case)
     */
    protected static $eventsToExclude = ['heartbeat'];

    /**
     * List of event ids which should be represented in the brief history report
     */
    protected static $briefEvents = [
        'section_exit_code',
        'test_exit_code',
        'test_pause',
        'test_run',
        'test_run',
        'test_authorise',
        'test_terminate',
        'test_irregularity',
        'pause',
        'unsecured-launch-prohibited',
        'focus-loss-prohibited',
        'leave-fullscreen-prohibited',
        'pause-on-disconnect',
    ];

    /**
     * @var \core_kernel_classes_Resource[] list of user instances
     */
    private $authors = [];

    /**
     * @var \core_kernel_classes_Resource[]
     */
    private $authorRoles = [];

    /**
     * @var \core_kernel_classes_Resource[]
     */
    private $proctorRoles = [];

    /**
     * TestSessionHistoryService constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $roles = $this->getOption(self::PROCTOR_ROLES);
        if(is_null($roles)){
            $roles = [];
        }
        $this->proctorRoles = array_merge([new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole')], $roles);

    }

    /**
     * @param array $sessions List of session ids
     * @param array $options The following option is handled:
     * - periodStart: a date/time string.
     * - periodEnd: a date/time string.
     * - detailed: whether to retrieve detailed or brief report. Defaults to false (brief).
     * - sortBy: column name string.
     * - sortOrder: order direction (asc|desc) string.
     * @return array
     */
    public function getSessionsHistory(array $sessions, $options)
    {
        $history = [];
        $periodStart = $this->getPeriodStart($options);
        $periodEnd = $this->getPeriodEnd($options);

        /** @var DeliveryLog $deliveryLog */
        $deliveryLog = $this->getServiceManager()->get(DeliveryLog::SERVICE_ID);

        //empty array means that all events (except listed in self::$eventsToExclude) will be represented in the report
        $eventsToInclude = $options['detailed'] ? [] : self::$briefEvents;

        foreach ($sessions as $sessionUri) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($sessionUri);
            $logs = $deliveryLog->get($deliveryExecution->getIdentifier());
            $exportable = [];

            foreach ($logs as $data) {
                $eventId = isset($data['data']['type']) ? $data['data']['type'] : $data[DeliveryLog::EVENT_ID];
                $eventName = strtolower(explode('.', $eventId)[0]);

                if (
                    (!empty($eventsToInclude) && !in_array($eventName, $eventsToInclude)) || //event should not be included
                    in_array($eventName, self::$eventsToExclude) //event must be excluded
                ) {
                    continue;
                }

                $author = $this->getAuthor($data);
                $details = $this->getEventDetails($data);
                $context = $this->getEventContext($data);
                $role = $this->getUserRole($author);

                $exportable['timestamp'] = (isset($data['data']['timestamp']))?$data['data']['timestamp']:$data['created_at'];
                if (($periodStart && $exportable['timestamp'] < $periodStart) || ($periodEnd && $exportable['timestamp'] > $periodEnd)) {
                    continue;
                }
                $exportable['date'] = DateHelper::displayeDate($exportable['timestamp'], DateHelper::FORMAT_LONG_MICROSECONDS);
                $exportable['role'] = $role;
                $exportable['actor'] = _dh($this->getActorName($author->getUri()));
                $exportable['event'] = $eventId;
                $exportable['details'] = $details;
                $exportable['context'] = $context;
                $history[] = $exportable;
            }
        }

        $this->sortHistory($history, $options);

        return $history;
    }

    /**
     * Gets the url that leads to the page listing the history
     * @param $delivery
     * @return string
     */
    public function getHistoryUrl($delivery = null)
    {
        $params = [];
        if ($delivery) {
            if ($delivery instanceof \core_kernel_classes_Resource) {
                $delivery = $delivery->getUri();
            }
            $params['delivery'] = $delivery . '';
        }
        return _url('index', 'Reporting', 'taoProctoring', $params);
    }

    /**
     * Gets the back url that returns to the page listing the sessions
     * @param $delivery
     * @return string
     */
    public function getBackUrl($delivery = null)
    {
        $params = [];
        if ($delivery) {
            if ($delivery instanceof \core_kernel_classes_Resource) {
                $delivery = $delivery->getUri();
            }
            $params['delivery'] = $delivery . '';
        }
        return _url('index', 'Monitor', 'taoProctoring', $params);
    }


    /**
     * @param array $data event data from delivery log
     * @return string
     */
    private function getEventDetails($data)
    {
        $details = '';
        if(isset($data['data']['type'])) {
            $details = (isset($data['data']['context']['shortcut'])) ? $data['data']['context']['shortcut']: '';
        } else {
            if (isset($data['data']['reason']) && isset($data['data']['reason']['reasons'])) {
                $details = is_array($data['data']['reason']['reasons']) ?
                    array_merge(array_values($data['data']['reason']['reasons']), [$data['data']['reason']['comment']])
                    : array_merge([$data['data']['reason']['reasons']], [$data['data']['reason']['comment']]);
            } else if (isset($data['data']['exitCode'])) {
                $details = $data['data']['exitCode'];
            } else if (isset($data['data']['itemId'])) {
                $details = $data['data']['itemId'];
            } else if (isset($data['data']['web_browser_name'])) {
                $details = ($data['data']['web_browser_name'] . ' ') .
                    (isset($data['data']['web_browser_version']) ? $data['data']['web_browser_version'] . '; ' : '') .
                    (isset($data['data']['os_name']) ? $data['data']['os_name'] . ' ' : '') .
                    (isset($data['data']['os_version']) ? $data['data']['os_version'] . ' ' : '');
            } else if (is_string($data['data'])) {
                $details = $data['data'];
            }
        }
        return $details;
    }

    /**
     * @param array $data event data from delivery log
     * @return string
     */
    private function getEventContext($data)
    {
        if (isset($data['data']['type'])) {
            $context = (isset($data['data']['context']['readable']))?$data['data']['context']['readable'] : '';
        } else {
            $context = (isset($data['data']['context']) && !is_null($data['data']['context'])) ? $data['data']['context'] : '';
        }
        return $context;
    }

    /**
     * @param $options
     * @return null|number timestamp
     */
    private function getPeriodStart(array $options)
    {
        $periodStart = null;

        if (!empty($options['periodStart'])) {
            $periodStart = new DateTime($options['periodStart']);
            $periodStart->setTime(0, 0, 0);
            $periodStart = DateHelper::getTimeStamp($periodStart->getTimestamp());
        }
        return $periodStart;
    }

    /**
     * @param $options
     * @return null|number timestamp
     */
    private function getPeriodEnd(array $options)
    {
        $periodEnd = null;

        if (!empty($options['periodEnd'])) {
            $periodEnd = new DateTime($options['periodEnd']);
            $periodEnd->setTime(23, 59, 59);
            $periodEnd = DateHelper::getTimeStamp($periodEnd->getTimestamp());
        }

        return $periodEnd;
    }

    /**
     * Sort events
     * @param array $history
     * @param array $options
     */
    private function sortHistory(array &$history, array $options)
    {
        $sortBy = isset($options['sortBy']) ? $options['sortBy'] : 'timestamp';
        $sortOrder = isset($options['sortOrder']) ? $options['sortOrder'] : 'desc';
        if ($sortOrder == 'asc') {
            $sortOrder = 1;
        } else {
            $sortOrder = -1;
        }
        if ($sortBy == 'timestamp' || $sortBy == 'id') {
            usort($history, function($a, $b) use($sortOrder) {
                $result = $sortOrder * (floatval($a['timestamp']) - floatval($b['timestamp']));
                if ($result === 0) {
                    return $result;
                }
                return $result > 0 ? 1 : -1;
            });
        } else {
            usort($history, function($a, $b) use($sortBy, $sortOrder) {
                return $sortOrder * strnatcasecmp($a[$sortBy], $b[$sortBy]);
            });
        }
    }

    /**
     * @param array $data event data from delivery log
     * @return \core_kernel_classes_Resource
     */
    protected function getAuthor(array $data)
    {
        if (!isset($this->authors[$data['created_by']])) {
            $this->authors[$data['created_by']] = new \core_kernel_classes_Resource($data['created_by']);
        }
        return $this->authors[$data['created_by']];
    }

    /**
     * @param $userId
     * @return string
     */
    protected function getActorName($userId)
    {
        $user = UserHelper::getUser($userId);

        return UserHelper::getUserName($user, true);
    }

    /**
     * @param \core_kernel_classes_Resource $user
     * @return string
     */
    private function getUserRole(\core_kernel_classes_Resource $user)
    {
        $userService = \tao_models_classes_UserService::singleton();
        if (!isset($this->authorRoles[$user->getUri()])) {
            $this->authorRoles[$user->getUri()] = ($userService->userHasRoles($user, $this->proctorRoles)) ? __('Proctor') : __('Test-Taker');
        }
        return $this->authorRoles[$user->getUri()];
    }
}

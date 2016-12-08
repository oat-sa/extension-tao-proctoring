<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\helpers;

use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoClientDiagnostic\model\storage\Storage;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\DiagnosticStorage;
use oat\taoProctoring\model\TestCenterService;
use core_kernel_classes_Resource;
use DateTime;
use tao_helpers_Date as DateHelper;
use oat\tao\helpers\UserHelper;
use oat\taoProctoring\model\implementation\DeliveryService;
use oat\taoProctoring\model\implementation\TestSessionHistoryService;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\PaginatedStorage;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class TestCenterHelper
{
    /**
     * Gets a list of available test sites
     *
     * @param array [$options]
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getTestCenters($options = array())
    {
        $testCenterService = TestCenterService::singleton();
        $currentUser = \common_session_SessionManager::getSession()->getUser();

        $testCenters = $testCenterService->getTestCentersByProctor($currentUser, $options);
        $entries = array();
        foreach ($testCenters as $testCenter) {
            $entries[] = array(
                'id' => $testCenter->getUri(),
                'url' => _url('testCenter', 'TestCenter', null, array('testCenter' => $testCenter->getUri())),
                'label' => $testCenter->getLabel(),
                'text' => __('Go to')
            );

        }
        return $entries;
    }

    /**
     * Gets a list of available test sites
     *
     * @param string $testCenterId
     * @return core_kernel_classes_Resource
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getTestCenter($testCenterId)
    {
        $testCenterService = TestCenterService::singleton();

        return $testCenterService->getTestCenter($testCenterId);
    }

    /**
     * Gets a list of entries available for a test site
     *
     * @param $testCenter core_kernel_classes_Resource
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getTestCenterActions(core_kernel_classes_Resource $testCenter)
    {

        $actionDiagnostics = BreadcrumbsHelper::diagnostics($testCenter);
        $actionDeliveries = BreadcrumbsHelper::deliveries($testCenter);

        $actions = array(
            array(
                'url' => $actionDeliveries['url'],
                'label' => __('Sessions'),
                'content' => __('Monitor and manage sessions for the test site'),
                'text' => __('Go')
            ),
            array(
                'url' => $actionDiagnostics['url'],
                'label' => __('Readiness Check'),
                'content' => __('Check the compatibility of the current workstation and see the results'),
                'text' => __('Go')
            ),
        );

        return $actions;
    }

    /**
     * Gets the client diagnostic config
     * @param core_kernel_classes_Resource $testCenter
     * @return array
     * @throws \common_ext_ExtensionException
     */
    public static function getDiagnosticConfig(core_kernel_classes_Resource $testCenter)
    {
        $config = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoClientDiagnostic')->getConfig('clientDiag');

        $config['extension'] = 'taoProctoring';
        $config['controller'] = 'DiagnosticChecker';
        $config['storeParams'] = ['testCenter' => $testCenter->getUri()];

        return $config;
    }

    /**
     * Gets the results for a particular id
     * @param core_kernel_classes_Resource $testCenter
     * @param $id
     * @return mixed
     * @throws \common_exception_NoImplementation
     */
    public static function getDiagnostic(core_kernel_classes_Resource $testCenter, $id)
    {
        $storageService = ServiceManager::getServiceManager()->get(Storage::SERVICE_ID);
        if ($storageService instanceof PaginatedStorage) {
            $diagnostic = $storageService->find($id);
            if ($testCenter->getUri() == $diagnostic[DiagnosticStorage::DIAGNOSTIC_TEST_CENTER]) {
                return $diagnostic;
            }
            return null;
        } else {
            throw new \common_exception_NoImplementation('The storage service provided to store the diagnostic results must be upgraded to support reads!');
        }
    }

    /**
     * Gets the list of readiness checks related to a test site
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array [$options]
     * @return array
     * @throws \common_exception_NoImplementation
     */
    public static function getDiagnostics(core_kernel_classes_Resource $testCenter, $options = array())
    {
        $storageService = ServiceManager::getServiceManager()->get(Storage::SERVICE_ID);
        if ($storageService instanceof PaginatedStorage) {
            $options[DataTableHelper::OPTION_FILTER] = [DiagnosticStorage::DIAGNOSTIC_TEST_CENTER => $testCenter->getUri()];
            return DataTableHelper::paginate($storageService, $options, function($data) {
                foreach($data as $idx => $row) {
                    $rowData = [
                        'id' => $row[DiagnosticStorage::DIAGNOSTIC_ID],
                        'workstation' => $row[DiagnosticStorage::DIAGNOSTIC_WORKSTATION] . ' (' . $row[DiagnosticStorage::DIAGNOSTIC_IP] . ')',
                        'os' => $row[DiagnosticStorage::DIAGNOSTIC_OS] . ' (' . $row[DiagnosticStorage::DIAGNOSTIC_OSVERSION] . ')',
                        'browser' => $row[DiagnosticStorage::DIAGNOSTIC_BROWSER] . ' (' . $row[DiagnosticStorage::DIAGNOSTIC_BROWSERVERSION] . ')',
                        'performance' => $row[DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_AVERAGE],
                        'bandwidth' => $row[DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MAX],
                    ];

                    if (isset($row[DiagnosticStorage::DIAGNOSTIC_CREATED_AT])) {
                        $dt = new DateTime($row[DiagnosticStorage::DIAGNOSTIC_CREATED_AT]);
                        $rowData['date'] = DateHelper::displayeDate($dt);
                    }

                    $data[$idx] = $rowData;
                }
                return $data;
            });
        } else {
            throw new \common_exception_NoImplementation('The storage service provided to store the diagnostic results must be upgraded to support reads!');
        }
    }

    /**
     * Gets the list of readiness checks related to a test site
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param $id
     * @return bool
     * @throws \common_exception_NoImplementation
     */
    public static function removeDiagnostic(core_kernel_classes_Resource $testCenter, $id)
    {
        $storageService = ServiceManager::getServiceManager()->get(Storage::SERVICE_ID);
        if ($storageService instanceof PaginatedStorage) {
            $ids = $id ? $id : [];
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            $filter = [
                DiagnosticStorage::DIAGNOSTIC_TEST_CENTER => $testCenter->getUri()
            ];

            foreach($ids as $id) {
                $storageService->delete($id, $filter);
            }
        } else {
            throw new \common_exception_NoImplementation('The storage service provided to store the diagnostic results must be upgraded to support deletions!');
        }
        return true;
    }

    /**
     * Gets the list of session history
     *
     * @param $sessions
     * @param bool $logHistory
     * @param array [$options]
     * @return array
     */
    public static function getSessionHistory($sessions, $logHistory = false, $options = array())
    {
        if ($logHistory) {
            foreach ($sessions as $sessionUri) {
                $deliveryLog = ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
                $deliveryLog->log($sessionUri, 'HISTORY', []);
            }
        }
        /** @var TestSessionHistoryService $historyService */
        $historyService = ServiceManager::getServiceManager()->get(TestSessionHistoryService::SERVICE_ID);
        return DataTableHelper::paginate($historyService->getSessionsHistory($sessions, $options), $options);

    }

    /**
     * Gets the list of assessment reports related to a test site
     *
     * @param $testCenter
     * @param array [$options]
     * @return array
     */
    public static function getReports($testCenter, $options = array())
    {
        $periodStart = null;
        $periodEnd = null;

        if (isset($options['periodStart'])) {
            $periodStart = new DateTime($options['periodStart']);
            $periodStart->setTime(0, 0, 0);
            $periodStart = DateHelper::getTimeStamp($periodStart->getTimestamp());
        }
        if (isset($options['periodEnd'])) {
            $periodEnd = new DateTime($options['periodEnd']);
            $periodEnd->setTime(23, 59, 59);
            $periodEnd = DateHelper::getTimeStamp($periodEnd->getTimestamp());
        }

        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $deliveries      = ServiceManager::getServiceManager()->get(EligibilityService::SERVICE_ID)->getEligibleDeliveries($testCenter);
        $filteredExecutions = array();
        foreach($deliveries as $delivery) {
            if ($delivery->exists()) {
                $deliveryExecutions = $deliveryService->getDeliveryExecutions($delivery->getUri());
                foreach ($deliveryExecutions as $deliveryExecution) {
                    $startTime = $deliveryExecution->getStartTime();
                    $finishTime = $deliveryExecution->getFinishTime();

                    if ($finishTime && $periodStart && $periodStart > DateHelper::getTimeStamp($finishTime)) {
                        continue;
                    }
                    if(!$finishTime && $periodStart && $periodEnd && ( DateHelper::getTimeStamp($startTime) > $periodEnd ||  DateHelper::getTimeStamp($startTime) < $periodStart )) {
                        continue;
                    }
                    if ($startTime && $periodEnd && $periodEnd < DateHelper::getTimeStamp($startTime)) {
                        continue;
                    }

                    $filteredExecutions[] = $deliveryExecution;
                }
            }
        }

        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        return DataTableHelper::paginate($filteredExecutions, $options, function($deliveryExecutions) use ($deliveryExecutionStateService) {
            $reports = [];

            foreach($deliveryExecutions as $deliveryExecution) {
                /* @var $deliveryExecution DeliveryExecution */
                $startTime = $deliveryExecution->getStartTime();
                $finishTime = $deliveryExecution->getFinishTime();

                $userId = $deliveryExecution->getUserIdentifier();
                $user = UserHelper::getUser($userId);

                $authorizationData = self::getDeliveryLog()->get($deliveryExecution->getIdentifier(), 'TEST_AUTHORISE');
                $proctor = empty($authorizationData) ? '' : UserHelper::getUser($authorizationData[0][DeliveryLog::DATA]['proctorUri']);

                $procActions = self::getProctorActions($deliveryExecution);
                $reports[] = array(
                    'id' => $deliveryExecution->getIdentifier(),
                    'delivery' => $deliveryExecution->getDelivery()->getLabel(),
                    'testtaker' => $user ? UserHelper::getUserName($user, true) : '',
                    'proctor' => $proctor ? UserHelper::getUserName($proctor, true) : '',
                    'status' => $deliveryExecutionStateService->getState($deliveryExecution),
                    'start' => $startTime ? DateHelper::displayeDate($startTime) : '',
                    'end' => $finishTime ? DateHelper::displayeDate($finishTime) : '',
                    'pause' => $procActions['pause'],
                    'resume' => $procActions['resume'],
                    'irregularities' => $procActions['irregularities'],
                );
            }

            return $reports;
        });
    }

    /**
     * @param $deliveryExecution
     * @return array
     */
    protected static function getProctorActions($deliveryExecution)
    {
        $actions = [];

        $irregularityReports = self::getActions($deliveryExecution->getIdentifier(), 'TEST_IRREGULARITY');
        $pausesReports = self::getActions($deliveryExecution->getIdentifier(), 'TEST_PAUSE', 'pause');
        $authorizeReports = self::getActions($deliveryExecution->getIdentifier(), 'TEST_AUTHORISE', 'resume');
        $terminateReports = self::getActions($deliveryExecution->getIdentifier(), 'TEST_TERMINATE', 'terminate');

        $actions['pause'] = strval(count($pausesReports));
        $actions['resume'] = strval(count($authorizeReports));
        $actions['irregularities'] = array_merge($irregularityReports, $pausesReports, $authorizeReports, $terminateReports);
        usort($actions['irregularities'], function ($a, $b) {
            if ($a['timestamp'] == $b['timestamp']) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return $actions;
    }

    /**
     * @param string $deliveryExecutionId
     * @param string $event
     * @param string $type
     * @return array
     */
    protected static function getActions($deliveryExecutionId, $event, $type = 'irregularity')
    {
        $irregularities = self::getDeliveryLog()->get($deliveryExecutionId, $event);
        $result = [];
        foreach($irregularities as $irregularityReport) {
            $data = $irregularityReport[DeliveryLog::DATA];
            $result[] = [
                'timestamp' => $irregularityReport[DeliveryLog::CREATED_AT],
                'type' => $type,
                'comment' => isset($data['comment']) ? $data['comment'] : '',
                'reasons' => isset($data['reasons']) ? $data['reasons'] : '',
            ];
        }
        return $result;
    }

    /**
     * @return DeliveryLog
     */
    protected static function getDeliveryLog()
    {
        return ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
    }
}

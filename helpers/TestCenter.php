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
use oat\taoProctoring\model\mock\WebServiceMock;
use oat\taoProctoring\model\TestCenterService;
use core_kernel_classes_Resource;
use core_kernel_users_GenerisUser;
use DateTime;
use tao_helpers_Date as DateHelper;
use oat\tao\helpers\UserHelper;
use oat\taoQtiTest\models\TestSessionMetaData;
use oat\taoProctoring\model\implementation\DeliveryService;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class TestCenter extends Proctoring
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

        $actionDiagnostics = Breadcrumbs::diagnostics($testCenter);
        $actionDeliveries = Breadcrumbs::deliveries($testCenter);
        $actionReporting = Breadcrumbs::reporting($testCenter);

        $actions = array(
            array(
                'url' => $actionDiagnostics['url'],
                'label' => __('Readiness Check'),
                'content' => __('Check the compatibility of the current workstation and see the results'),
                'text' => __('Go')
            ),
            array(
                'url' => $actionDeliveries['url'],
                'label' => __('Sessions'),
                'content' => __('Monitor and manage sessions for the test site'),
                'text' => __('Go')
            ),
            array(
                'url' => $actionReporting['url'],
                'label' => __('Assessment Activity Reporting'),
                'content' => __('Generate and review test histories'),
                'text' => __('Go')
            ),
        );

        return $actions;
    }

    /**
     * Gets the list of readiness checks related to a test site
     *
     * @param $testCenterId
     * @param array [$options]
     * @return array
     */
    public static function getDiagnostics($testCenterId, $options = array())
    {
        $diagnostics = WebServiceMock::loadJSON(dirname(__FILE__) . '/../mock/data/diagnostics.json');
        return self::paginate($diagnostics, $options);
    }

    /**
     * Gets a list of testtaker for a particular $delivery
     * @param string $deliveryId
     * @return array
     */
    private static function getTestTakers($deliveryId)
    {
        static $cache = array();
        if (!isset($cache[$deliveryId])) {
            $testTakers = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID)->getDeliveryTestTakers($deliveryId);
            $map = array();
            foreach($testTakers as $testTaker) {
                $map[$testTaker->getIdentifier()] = $testTaker;
            }
            $cache[$deliveryId] = $map;
        }
        return $cache[$deliveryId];
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
        $reports = array();

        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $deliveries      = $deliveryService->getTestCenterDeliveries($testCenter);
        foreach($deliveries as $delivery) {
            $deliveryExecutions = $deliveryService->getDeliveryExecutions($delivery->getUri());
            foreach($deliveryExecutions as $deliveryExecution) {
                $userId = $deliveryExecution->getUserIdentifier();
                $user = UserHelper::getUser($userId);

                $state = $deliveryService->getProctoringState($deliveryExecution->getUri());
                $proctor = '';
                if (!empty($state['authorized_by'])) {
                    $proctor = UserHelper::getUserName(UserHelper::getUser($state['authorized_by']), true);
                }
                
                $procActions = self::getProctorActions($deliveryExecution);
                $startTime = $deliveryExecution->getStartTime();
                $finishTime = $deliveryExecution->getFinishTime();

                $reports[] = array(
                    'id' => $deliveryExecution->getIdentifier(),
                    'delivery' => $delivery->getLabel(),
                    'testtaker' => UserHelper::getUserName($user, true),
                    'proctor' => $proctor,
                    'status' => $deliveryService->getState($deliveryExecution),
                    'start' => $startTime ? DateHelper::displayeDate($startTime) : '',
                    'end' => $finishTime ? DateHelper::displayeDate($finishTime) : '',
                    'pause' => $procActions['pause'],
                    'resume' => $procActions['resume'],
                    'irregularities' => $procActions['irregularities'],
                );
            }
        }

        // filter the reports by dates
        $start = isset($options['periodStart']) ? new DateTime(substr($options['periodStart'], 0, 10) . ' 00:00:00') : null;
        $end   = isset($options['periodEnd']) ? new DateTime(substr($options['periodEnd'], 0, 10) . ' 23:59:59') : null;

        if (!is_null($start) || !is_null($end)) {
            $returnValues = array();
            foreach ($reports as $delivery) {
                $_start = new DateTime($delivery['start']);
                $_end   = new DateTime($delivery['end']);
                if (!is_null($start) && $start > $_end) {
                    continue;
                }
                if (!is_null($end) && $end < $_start) {
                    continue;
                }
                $returnValues[] = $delivery;
            }
            $reports = $returnValues;
        }

        return self::paginate($reports, $options);
    }

    /**
     * @param $deliveryExecution
     * @return array
     */
    protected static function getProctorActions($deliveryExecution)
    {
        $ds = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $session = $ds->getTestSession($deliveryExecution);
        
        $actions = array(
            'pause' => 0,
            'resume' => 0,
            'irregularities' => array()
        );

        if (!is_null($session)) {
            $resultServer = \taoResultServer_models_classes_ResultServerStateFull::singleton();
            $vars = $resultServer->getVariables($session->getSessionId());
            
            foreach ($vars as $arr) {
                $var = reset($arr)->variable;
                if (substr($var->identifier, 0, strlen('TEST_PAUSE')) == 'TEST_PAUSE') {
                    $actions['pause']++;
                    $log = self::getProctorIrregularity($var);
                    $actions['irregularities'][] = self::getProctorIrregularity($var, 'pause');
                } elseif (substr($var->identifier, 0, strlen('TEST_AUTHORISE')) == 'TEST_AUTHORISE') {
                    $actions['resume']++;
                    $actions['irregularities'][] = self::getProctorIrregularity($var, 'resume');
                } elseif (substr($var->identifier, 0, strlen('TEST_TERMINATE')) == 'TEST_TERMINATE') {
                    $actions['irregularities'][] = self::getProctorIrregularity($var, 'terminate');
                } elseif (substr($var->identifier, 0, strlen('TEST_IRREGULARITY')) == 'TEST_IRREGULARITY') {
                    $actions['irregularities'][] = self::getProctorIrregularity($var);
                }
                
            }
        }
        return $actions;
    }

    /**
     * @param $var
     * @param string $type
     * @return array
     */
    protected static function getProctorIrregularity($var, $type = 'irregularity')
    {
        $trace = json_decode($var->trace, true);
        $data = array(
            'timestamp' => DateHelper::displayeDate($trace['timestamp']),
            'type' => $type
        );

        if (isset($trace['details'])) {
            if (!empty($trace['details']['reasons'])) {
                $data = array_merge($data, $trace['details']['reasons']);
            }
            if (!empty($trace['details']['comment'])) {
                $data['comment'] = $trace['details']['comment'];
            }
        }

        return $data;
    }
}

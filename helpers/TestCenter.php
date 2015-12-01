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
use \core_kernel_classes_Resource;
use \DateTime;
use tao_helpers_Date as DateHelper;

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
     * Gets the list of assessment reports related to a test site
     *
     * @param $testCenter
     * @param array [$options]
     * @return array
     */
    public static function getReports($testCenter, $options = array())
    {
        $count = 10;

        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $currentUser = \common_session_SessionManager::getSession()->getUser();
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        $deliveryExecutions = [];
        foreach ($deliveries as $delivery) {
            $deliveryExecutions = $deliveryExecutions + $deliveryService->getDeliveryExecutions($delivery->getId());
        }

        $start = isset($options['periodStart']) ? new DateTime($options['periodStart']) : null;
        $end = isset($options['periodEnd']) ? new DateTime($options['periodEnd']) : null;
        if ($end !== null) {
            $end->add(new \DateInterval('P1D'));
        }

        $deliveryExecutions = array_filter($deliveryExecutions, function ($deliveryExecution) use($start, $end) {
            $result = true;
            if ($start !== null) {
                $result = $result && $start->getTimestamp() < DateHelper::getTimeStamp($deliveryExecution->getStartTime());
            }
            if ($end !== null) {
                $result = $result && $end->getTimestamp() > DateHelper::getTimeStamp($deliveryExecution->getFinishTime());
            }
            return $result;
        });

        function getTestTakers($deliveryId, $deliveryService)
        {
            static $cache = array();
            if (!isset($cache[$deliveryId])) {
                $cache[$deliveryId] = $deliveryService->getDeliveryTestTakers($deliveryId);
            }
            return $cache[$deliveryId];
        }

        $results = [];

        foreach ($deliveryExecutions as $deliveryExecution) {
            $results[] = [
                'id' => $deliveryExecution->getIdentifier(),
                'proctor' => self::getUserName($currentUser),
                'start' => DateHelper::getTimeStamp($deliveryExecution->getStartTime()),
                'end' => DateHelper::getTimeStamp($deliveryExecution->getFinishTime()),
                'delivery' => $delivery->getLabel(),
                'testtaker' => $deliveryExecution->getUserIdentifier(),
                'status' => $deliveryService->getState($deliveryExecution),
                'pause' => 0, //TODO implement counter
                'resume' => 0, //TODO implement counter
                'irregularities' => '', //WebServiceMock::random($irregularity),
            ];
        }

        return self::paginate($results, $options);
    }
}
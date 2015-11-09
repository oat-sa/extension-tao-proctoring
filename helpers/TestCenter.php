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
use \core_kernel_classes_Resource;
use \DateTime;

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
        $testCenterService = ServiceManager::getServiceManager()->get('taoProctoring/testCenter');

        $testCenters = $testCenterService->getTestCenters($options);
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
        $testCenterService = ServiceManager::getServiceManager()->get('taoProctoring/testCenter');

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
                'label' => __('Deliveries'),
                'content' => __('Monitor and manage the deliveries of the test site'),
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
        $testCenterService = ServiceManager::getServiceManager()->get('taoProctoring/testCenter');
        $diagnostics = $testCenterService->getDiagnostics($testCenterId);
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
        $currentUser     = \common_session_SessionManager::getSession()->getUser();
        $deliveries      = $deliveryService->getProctorableDeliveries($currentUser);

        function getTestTakers($deliveryId, $deliveryService)
        {
            static $cache = array();
            if (!isset($cache[$deliveryId])) {
                $cache[$deliveryId] = $deliveryService->getDeliveryTestTakers($deliveryId);
            }
            return $cache[$deliveryId];
        }

        $status       = array('Completed', 'Terminated', 'Pending', 'Paused', 'Running');
        $date         = array('2015-09-16 13:04', '2015-09-21 10:23', '2015-10-06 09:34', '2015-10-18 11:43', '2015-10-29 14:53');
        $irregularity = array('', '', 'cell phone ringing', '', '', 'sickness break / restroom for 10 min', '', '');
        $breaks       = array(0, 0, 1, 0, 0, 2, 0, 0, 3, 0, 0);
        $results      = array();

        if (!empty($deliveries)) {
            for ($i = 0; $i < $count; $i ++) {
                $id = $i + 1;

                $delivery   = WebServiceMock::random($deliveries);
                if (is_object($delivery)) {
                    $testTakers = getTestTakers($delivery->getId(), $deliveryService);
                    $break      = WebServiceMock::random($breaks);

                    $results[] = array(
                        'id' => $id,
                        'delivery' => $delivery->getLabel(),
                        'testtaker' => self::getUserName(WebServiceMock::random($testTakers)),
                        'proctor' => self::getUserName($currentUser),
                        'status' => WebServiceMock::random($status),
                        'start' => WebServiceMock::random($date),
                        'end' => WebServiceMock::random($date),
                        'pause' => $break,
                        'resume' => $break,
                        'irregularities' => WebServiceMock::random($irregularity),
                    );
                }
            }
        }

        $start        = isset($options['periodStart']) ? new DateTime($options['periodStart']) : null;
        $end          = isset($options['periodEnd']) ? new DateTime($options['periodEnd']) : null;

        if (!is_null($start) || !is_null($end)) {
            $returnValues = array();
            foreach ($results as $delivery) {
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
            $results = $returnValues;
        }

        return self::paginate($results, $options);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: jsc
 * Date: 06/11/15
 * Time: 10:47
 */

namespace oat\taoProctoring\helpers;

use oat\oatbox\service\ServiceManager;
use \core_kernel_classes_Resource;

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

                $delivery   = $deliveries[array_rand($deliveries)];
                $testTakers = getTestTakers($delivery->getId(), $deliveryService);
                $break      = $breaks[array_rand($breaks)];

                $results[] = array(
                    'id' => $id,
                    'delivery' => $delivery->getLabel(),
                    'testtaker' => self::getUserName($testTakers[array_rand($testTakers)]),
                    'proctor' => self::getUserName($currentUser),
                    'status' => $status[array_rand($status)],
                    'start' => $date[array_rand($date)],
                    'end' => $date[array_rand($date)],
                    'pause' => $break,
                    'resume' => $break,
                    'irregularities' => $irregularity[array_rand($irregularity)],
                );
            }
        }

        return self::paginate($results, $options);
    }
}
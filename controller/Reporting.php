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

namespace oat\taoProctoring\controller;

use oat\taoProctoring\controller\Proctoring;
use oat\taoProctoring\helpers\Breadcrumbs;

/**
 * Proctoring Delivery controllers
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Reporting extends Proctoring
{

    /**
     * Display the activity reporting of the current test center
     */
    public function index()
    {

        $testCenter     = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();
        $reports        = $this->getReports($testCenter);
        $reports        = $this->filterDatetime($reports, $requestOptions);

        $this->setData('title', __('Assessment Activity Reporting for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'reporting-index',
            array(
            'id' => $testCenter->getUri(),
            'set' => $this->paginate($reports, $requestOptions),
            ),
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
            Breadcrumbs::reporting(
                $testCenter,
                array(
                Breadcrumbs::diagnostics($testCenter),
                Breadcrumbs::deliveries($testCenter),
                )
            )
        ));
    }

    /**
     * Gets the list of assessment reports related to a test site
     *
     * @param $testCenter
     * @return array
     */
    private function getReports($testCenter)
    {
        $count = 10;

        $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
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

        function getUserStringProp($user, $property)
        {
            $value = $user->getPropertyValues($property);
            return empty($value) ? '' : current($value);
        }

        function getUserName($user)
        {
            $firstName = getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName  = getUserStringProp($user, PROPERTY_USER_LASTNAME);
            if (empty($firstName) && empty($lastName)) {
                $firstName = getUserStringProp($user, RDFS_LABEL);
            }

            return $firstName.' '.$lastName;
        }
        $status       = array('Completed', 'Terminated', 'Pending', 'Paused', 'Running');
        $date         = array('2015-09-16 13:04', '2015-09-21 10:23', '2015-10-06 09:34', '2015-10-18 11:43', '2015-10-29 14:53', '2015-11-8 09:36');
        $irregularity = array('', '', 'cell phone ringing', '', '', 'sickness break / restroom for 10 min', '', '');
        $breaks       = array(0, 0, 1, 0, 0, 2, 0, 0, 3, 0, 0);
        $results      = array();

        if (!empty($deliveries) && !empty($testTakers)) {
            for ($i = 0; $i < $count; $i ++) {
                $id = $i + 1;

                $delivery   = $deliveries[array_rand($deliveries)];
                $testTakers = getTestTakers($delivery->getId(), $deliveryService);
                $break      = $breaks[array_rand($breaks)];

                $results[] = array(
                    'id' => $id,
                    'delivery' => $delivery->getLabel(),
                    'testtaker' => getUserName($testTakers[array_rand($testTakers)]),
                    'proctor' => getUserName($currentUser),
                    'status' => $status[array_rand($status)],
                    'start' => $date[array_rand($date)],
                    'end' => $date[array_rand($date)],
                    'pause' => $break,
                    'resume' => $break,
                    'irregularities' => $irregularity[array_rand($irregularity)],
                );
            }
        }
        return $results;
    }

    /**
     * Add a simple php level report filtering
     *
     * @todo replace this with an actually filtering on the db query ?
     * @param type $deliveries
     * @param type $requestOptions
     * @return type
     */
    private function filterDatetime($deliveries, $requestOptions)
    {
        $returnValues = array();
        $start        = isset($requestOptions['start']) ? new DateTime($requestOptions['start']) : null;
        $end          = isset($requestOptions['end']) ? new DateTime($requestOptions['end']) : null;
        
        if (!is_null($start) || !is_null($end)) {
            foreach ($deliveries as $delivery) {
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
        }

        return $returnValues;
    }
}
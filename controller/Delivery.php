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
use \core_kernel_classes_Resource;

/**
 * Proctoring Delivery controllers
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Delivery extends Proctoring
{

    /**
     * Displays the index page of the deliveries list all available deliveries for the current test center
     */
    public function index()
    {

        $testCenter = $this->getCurrentTestCenter();
        $deliveries = $this->getDeliveries($testCenter);

        $this->composeView(
            'delivery-index',
            array(
                'list' => $deliveries
            ),
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
            Breadcrumbs::deliveries(
                $testCenter,
                array(
                    Breadcrumbs::diagnostics($testCenter),
                    Breadcrumbs::reporting($testCenter)
                )
            )
        ));
    }

    /**
     * Display all delivery executions of the selected delivery and test center
     */
    public function monitoring()
    {

        $testCenter    = $this->getCurrentTestCenter();
        $delivery      = $this->getCurrentDelivery();
        $requestOptions = $this->getRequestOptions();
        $executionData = $this->getDeliveryExecutions($delivery);

        $this->composeView(
            'delivery-monitoring',
            array(
                'delivery' => $delivery->getUri(),
                'testCenter' => $testCenter->getUri(),
                'set' => $this->paginate($executionData, $requestOptions)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
                Breadcrumbs::deliveries(
                    $testCenter,
                    array(
                        Breadcrumbs::diagnostics($testCenter),
                        Breadcrumbs::reporting($testCenter)
                    )
                ),
                Breadcrumbs::deliveryMonitoring($testCenter, $delivery, $this->getDeliveries())
            )
        );
    }

    /**
     * List available test takers to assign to a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function testTakers() {

        $delivery = $this->getCurrentDelivery();
        $testCenter = $this->getCurrentTestCenter();
        
        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
            $currentUser = \common_session_SessionManager::getSession()->getUser();

            $requestOptions = $this->getRequestOptions();
            $users = $deliveryService->getAvailableTestTakers($currentUser, $delivery->getUri(), $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

            $this->composeView(
                'delivery-testtakers',
                array(
                    'delivery' => $delivery->getUri(),
                    'title' =>  __('Assign test takers to %s', $delivery->getLabel()),
                    'testCenter' => $testCenter->getUri(),
                    'set' => $testTakers //change it to list for consistency
                ),array(
                    Breadcrumbs::testCenters(),
                    Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
                    Breadcrumbs::deliveries(
                        $testCenter,
                        array(
                            Breadcrumbs::diagnostics($testCenter),
                            Breadcrumbs::reporting($testCenter)
                        )
                    ),
                    Breadcrumbs::deliveryMonitoring($testCenter, $delivery, $this->getDeliveries()),
                    Breadcrumbs::deliveryTestTaker($testCenter, $delivery)
                )
            );

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Get a list of test takers usable in a table page
     * @param User[] $users
     * @param array $options
     * @return array
     */
    private function getTestTakersPage($users, $options) {
        $page = $this->paginate($users, $options);

        $list = array();
        foreach($page['data'] as $user) {
            /* @var $user User */
            $firstName = $this->getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = $this->getUserStringProp($user, PROPERTY_USER_LASTNAME);
            if (empty($firstName) && empty($lastName)) {
                $firstName = $this->getUserStringProp($user, RDFS_LABEL);
            }

            $list[] = array(
                'id' => $user->getIdentifier(),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'company' => '',
                'status' => ''
            );
        }

        $page['data'] = $list;

        return $page;
    }

    /**
     * Gets the list of test takers assigned to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function deliveryTestTakers() {

        $deliveryId = $this->getRequestParameter('id');

        try {

//            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
//
//            $requestOptions = $this->getRequestOptions();
//            $users = $deliveryService->getDeliveryTestTakers($deliveryId, $requestOptions);
//            $testTakers = $this->getTestTakersPage($users, $requestOptions);
   
            //@TODO mock list of test taker
            $this->returnJson(array());

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of test takers available for the proctor
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function availableTestTakers() {

        $deliveryId = $this->getRequestParameter('id');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $requestOptions = $this->getRequestOptions();
            $proctor = \common_session_SessionManager::getSession()->getUser();
            $users = $deliveryService->getAvailableTestTakers($proctor, $deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

            $this->returnJson($testTakers);

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Assign a test taker to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function assign() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->assignTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Authorise a test taker to run the delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function authorise() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->authoriseTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Remove a test taker from a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function remove() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->unassignTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Get the agregated data for a filtered set of delivery executions of a given delivery
     * This is performance critical, would need to find a way to optimize to obtain such information
     *
     * @return array
     */
    private function getDeliveryExecutions(core_kernel_classes_Resource $delivery)
    {

        $entries = array();

        $entries[] = array(
            'uri' => 'locam_ns#i4000000001',
            'testTaker' => array(
                'firstName' => 'Giacomo',
                'lastName' => 'Guilizzoni',
                'companyName' => 'Company ABC',
            ),
            'delivery' => array(
                'label' => 'Test A',
            ),
            'state' => array(
                //client will infer possible action based on the current status
                'status' => 'inProgress',
                'section' => array(
                    'label' => 'section B',
                    'position' => 2,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 1,
                    'total' => 9,
                    'time' => array(
                        //time unit in second, does not require microsecond precision for human monitoring
                        'elapsed' => 60,
                        'total' => 600
                    )
                )
            )
        );

        $entries[] = array(
            'uri' => 'locam_ns#i4000000002',
            'testTaker' => array(
                'firstName' => 'Marco',
                'lastName' => 'Botton',
                'companyName' => 'Company ABC',
            ),
            'delivery' => array(
                'label' => 'Test A',
            ),
            'state' => array(
                'status' => 'inProgress',
                'section' => array(
                    'label' => 'section A',
                    'position' => 1,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 5,
                    'total' => 8,
                    'time' => array(
                        'elapsed' => 540,
                        'total' => 600
                    )
                )
            )
        );

        return $entries;
    }
}
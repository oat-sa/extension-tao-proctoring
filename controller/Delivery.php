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

use oat\taoProctoring\helpers\Breadcrumbs;
use oat\taoProctoring\helpers\Delivery as DeliveryHelper;
use oat\taoProctoring\helpers\TestCenter as TestCenterHelper;

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
        $deliveries = DeliveryHelper::getDeliveries($testCenter);

        $this->composeView(
            'delivery-index',
            array(
                'list' => $deliveries
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
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

        $this->composeView(
            'delivery-monitoring',
            array(
                'delivery' => $delivery->getUri(),
                'testCenter' => $testCenter->getUri(),
                'set' => DeliveryHelper::getCurrentDeliveryExecutions($delivery, $requestOptions)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                Breadcrumbs::deliveries(
                    $testCenter,
                    array(
                        Breadcrumbs::diagnostics($testCenter),
                        Breadcrumbs::reporting($testCenter)
                    )
                ),
                Breadcrumbs::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter))
            )
        );
    }
    
    /**
     * Displays all delivery executions of ALL deliveries in the test center
     */
    public function monitoringAll()
    {

        $testCenter    = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->composeView(
            'delivery-monitoring',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => DeliveryHelper::getAllCurrentDeliveriesExecutions($testCenter, $requestOptions)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                Breadcrumbs::deliveries(
                    $testCenter,
                    array(
                        Breadcrumbs::diagnostics($testCenter),
                        Breadcrumbs::reporting($testCenter)
                    )
                ),
                Breadcrumbs::deliveryMonitoringAll($testCenter, DeliveryHelper::getDeliveries($testCenter))
            )
        );
    }

    /**
     * Lists the test takers assigned to a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function manage() {

        $delivery = $this->getCurrentDelivery();
        $testCenter = $this->getCurrentTestCenter();

        try {

            $requestOptions = $this->getRequestOptions();

            $this->composeView(
                'delivery-manager',
                array(
                    'delivery' => $delivery->getUri(),
                    'testCenter' => $testCenter->getUri(),
                    'set' => DeliveryHelper::getDeliveryTestTakers($delivery, $requestOptions),
                ),array(
                    Breadcrumbs::testCenters(),
                    Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                    Breadcrumbs::deliveries(
                        $testCenter,
                        array(
                            Breadcrumbs::diagnostics($testCenter),
                            Breadcrumbs::reporting($testCenter)
                        )
                    ),
                    Breadcrumbs::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter)),
                    Breadcrumbs::deliveryManage($testCenter, $delivery)
                )
            );

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Lists the available test takers to assign to a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function testTakers() {

        $delivery = $this->getCurrentDelivery();
        $testCenter = $this->getCurrentTestCenter();
        
        try {

            $requestOptions = $this->getRequestOptions();
            $testTakers = DeliveryHelper::getAvailableTestTakers($delivery->getUri(), $requestOptions);

            $this->composeView(
                'delivery-testtakers',
                array(
                    'delivery' => $delivery->getUri(),
                    'testCenter' => $testCenter->getUri(),
                    'set' => $testTakers //change it to list for consistency
                ),array(
                    Breadcrumbs::testCenters(),
                    Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                    Breadcrumbs::deliveries(
                        $testCenter,
                        array(
                            Breadcrumbs::diagnostics($testCenter),
                            Breadcrumbs::reporting($testCenter)
                        )
                    ),
                    Breadcrumbs::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter)),
                    Breadcrumbs::deliveryTestTakers($testCenter, $delivery)
                )
            );

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of current executions for a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function deliveryExecutions() {

        try {

            $delivery      = $this->getCurrentDelivery();
            $requestOptions = $this->getRequestOptions();

            $this->returnJson(DeliveryHelper::getCurrentDeliveryExecutions($delivery, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of current executions for a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function allDeliveriesExecutions() {

        try {

            $testCenter      = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions();

            $this->returnJson(DeliveryHelper::getAllCurrentDeliveriesExecutions($testCenter, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of test takers assigned to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function deliveryTestTakers() {

        try {

            $delivery      = $this->getCurrentDelivery();
            $requestOptions = $this->getRequestOptions();

            $this->returnJson(DeliveryHelper::getDeliveryTestTakers($delivery, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of test takers assigned to all deliveries
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function allDeliveriesTestTakers() {

        try {

            $testCenter      = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions();
            
            $this->returnJson(DeliveryHelper::getAllDeliveryTestTakers($testCenter, $requestOptions));
            

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

        $deliveryId = $this->getRequestParameter('delivery');

        try {

            $requestOptions = $this->getRequestOptions();
            $testTakers = DeliveryHelper::getAvailableTestTakers($deliveryId, $requestOptions);
            $this->returnJson($testTakers);

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Assigns a test taker to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function assignTestTakers() {

        $deliveryId = $this->getRequestParameter('delivery');
        $testTakerId = $this->getRequestParameter('testtaker');

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
     * Removes a test taker from a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function removeTestTakers() {

        $deliveryId = $this->getRequestParameter('delivery');
        $testTakerId = $this->getRequestParameter('testtaker');

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
     * Authorises a delivery execution
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function authoriseExecutions() {

        $deliveryId = $this->getRequestParameter('delivery');
        $testTakerId = $this->getRequestParameter('testtaker');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->authoriseExecutions($ttId, $deliveryId) && $result;
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
     * Terminates delivery executions
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function terminateExecutions() {

        $deliveryId = $this->getRequestParameter('delivery');
        $testTakerId = $this->getRequestParameter('testtaker');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->terminateExecutions($ttId, $deliveryId) && $result;
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
     * Pauses delivery executions
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function pauseExecutions() {

        $deliveryId = $this->getRequestParameter('delivery');
        $testTakerId = $this->getRequestParameter('testtaker');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->pauseExecutions($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }
}

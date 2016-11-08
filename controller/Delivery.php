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

use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\helpers\TestCenterHelper;

/**
 * Proctoring Delivery controllers
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Delivery extends ProctoringModule
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
                'testcenter' => $testCenter->getUri(),
                'list' => $deliveries,
                'categories' => $this->getAllReasonsCategories()
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::deliveries(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::diagnostics($testCenter),
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
        $requestOptions = $this->getRequestOptions(['sortby' => 'date', 'sortorder' => 'desc']);

        /** @var $assessmentResultsService \oat\taoProctoring\model\AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get('taoProctoring/AssessmentResults');

        $this->composeView(
            'delivery-monitoring',
            array(
                'delivery' => $delivery->getUri(),
                'testCenter' => $testCenter->getUri(),
                'set' => DeliveryHelper::getCurrentDeliveryExecutions($delivery, $testCenter, $requestOptions),
                'extrafields' => DeliveryHelper::getExtraFields(),
                'categories' => $this->getAllReasonsCategories(),
                'printReportButton' => json_encode($assessmentResultsService->getOption($assessmentResultsService::OPTION_PRINT_REPORT_BUTTON)),
                'timeHandling' => json_encode($assessmentResultsService->getOption($assessmentResultsService::OPTION_TIME_HANDLING)),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::deliveries(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::diagnostics($testCenter),
                    )
                ),
                BreadcrumbsHelper::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter))
            ),
            'Monitoring/index.tpl'
        );
    }

    /**
     * Displays all delivery executions of ALL deliveries in the test center
     */
    public function monitoringAll()
    {

        $testCenter    = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions(['sortby' => 'date', 'sortorder' => 'desc']);

        /** @var $assessmentResultsService \oat\taoProctoring\model\AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get('taoProctoring/AssessmentResults');

        $this->composeView(
            'delivery-monitoring',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => DeliveryHelper::getAllCurrentDeliveriesExecutions($testCenter, $requestOptions),
                'extrafields' => DeliveryHelper::getExtraFields(),
                'categories' => $this->getAllReasonsCategories(),
                'printReportButton' => json_encode($assessmentResultsService->getOption($assessmentResultsService::OPTION_PRINT_REPORT_BUTTON)),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::deliveries(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::diagnostics($testCenter),
                    )
                ),
                BreadcrumbsHelper::deliveryMonitoringAll($testCenter, DeliveryHelper::getDeliveries($testCenter)),
            ),
            'Monitoring/index.tpl'
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
                    'set' => DeliveryHelper::getDeliveryTestTakers($delivery, $testCenter->getUri(), $requestOptions),
                ),array(
                    BreadcrumbsHelper::testCenters(),
                    BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                    BreadcrumbsHelper::deliveries(
                        $testCenter,
                        array(
                            BreadcrumbsHelper::diagnostics($testCenter),
                        )
                    ),
                    BreadcrumbsHelper::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter)),
                    BreadcrumbsHelper::manageTestTakers($testCenter, $delivery, 'manage')
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
            $testTakers = DeliveryHelper::getAvailableTestTakers($delivery, $testCenter, $requestOptions);

            $this->composeView(
                'delivery-testtakers',
                array(
                    'delivery' => $delivery->getUri(),
                    'testCenter' => $testCenter->getUri(),
                    'set' => $testTakers //change it to list for consistency
                ),array(
                    BreadcrumbsHelper::testCenters(),
                    BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                    BreadcrumbsHelper::deliveries(
                        $testCenter,
                        array(
                            BreadcrumbsHelper::diagnostics($testCenter),
                        )
                    ),
                    BreadcrumbsHelper::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter)),
                    BreadcrumbsHelper::manageTestTakers($testCenter, $delivery, 'testTakers')
                )
            );

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of the deliveries for the current test center
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function deliveries() {

        try {

            $testCenter = $this->getCurrentTestCenter();
            $deliveries = DeliveryHelper::getDeliveries($testCenter);
            $this->returnJson($deliveries);

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
            $testCenter      = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions(['sortby' => 'date', 'sortorder' => 'desc']);

            $this->returnJson(DeliveryHelper::getCurrentDeliveryExecutions($delivery, $testCenter, $requestOptions));

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
            $requestOptions = $this->getRequestOptions(['sortby' => 'date', 'sortorder' => 'desc']);

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
            $testCenter      = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions();

            $this->returnJson(DeliveryHelper::getDeliveryTestTakers($delivery, $testCenter->getUri(), $requestOptions));

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

        $delivery = $this->getCurrentDelivery();
        $testCenter = $this->getCurrentTestCenter();

        try {

            $requestOptions = $this->getRequestOptions();
            $testTakers = DeliveryHelper::getAvailableTestTakers($delivery, $testCenter, $requestOptions);
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
    public function assignTestTakers()
    {
        $deliveryId = $this->getRequestParameter('delivery');
        $testTakers = $this->getRequestParameter('testtaker');
        $testCenterId = $this->getRequestParameter('testCenter');

        if (!is_array($testTakers)) {
            $testTakers = array($testTakers);
        }

        try {

            $added = DeliveryHelper::assignTestTakers($testTakers, $deliveryId, $testCenterId);
            $notAdded = array_diff($testTakers, $added);

            $this->returnJson(array(
                'success' => !count($notAdded),
                'processed' => $added,
                'unprocessed' => $notAdded
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
    public function removeTestTakers()
    {
        $deliveryId = $this->getRequestParameter('delivery');
        $testTakers = $this->getRequestParameter('testtaker');
        $testCenterId = $this->getRequestParameter('testCenter');

        if (!is_array($testTakers)) {
            $testTakers = array($testTakers);
        }

        try {

            $removed = DeliveryHelper::unassignTestTakers($testTakers, $deliveryId, $testCenterId);
            $notRemoved = array_diff($testTakers, $removed);

            $this->returnJson(array(
                'success' => !count($notRemoved),
                'processed' => $removed,
                'unprocessed' => $notRemoved
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
    public function authoriseExecutions()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $reason = $this->getRequestParameter('reason');
        $testCenter = $this->getRequestParameter('testCenter');

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {

            $authorised = DeliveryHelper::authoriseExecutions($deliveryExecution, $reason, $testCenter);
            $notAuthorised = array_diff($deliveryExecution, $authorised);

            $this->returnJson(array(
                'success' => !count($notAuthorised),
                'processed' => $authorised,
                'unprocessed' => $notAuthorised
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
    public function terminateExecutions()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $reason = $this->getRequestParameter('reason');

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {

            $terminated = DeliveryHelper::terminateExecutions($deliveryExecution, $reason);
            $notTerminated = array_diff($deliveryExecution, $terminated);

            $this->returnJson(array(
                'success' => !count($notTerminated),
                'processed' => $terminated,
                'unprocessed' => $notTerminated
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
    public function pauseExecutions()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $reason = $this->getRequestParameter('reason');

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {

            $paused = DeliveryHelper::pauseExecutions($deliveryExecution, $reason);
            $notPaused = array_diff($deliveryExecution, $paused);

            $this->returnJson(array(
                'success' => !count($notPaused),
                'processed' => $paused,
                'unprocessed' => $notPaused
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }

    /**
     * Report irregularities in delivery executions
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function reportExecutions()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $reason = $this->getRequestParameter('reason');

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {

            $reported = DeliveryHelper::reportExecutions($deliveryExecution, $reason);
            $notReported = array_diff($deliveryExecution, $reported);

            $this->returnJson(array(
                'success' => !count($notReported),
                'processed' => $reported,
                'unprocessed' => $notReported
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }
}

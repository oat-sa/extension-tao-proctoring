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
use oat\oatbox\service\ServiceManager;

/**
 * Proctoring Reporting controllers for the assessment activity reporting screen.
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Reporting extends ProctoringModule
{
    /**
     * Display the session history of the current test center
     */
    public function sessionHistory()
    {
        $testCenter     = $this->getCurrentTestCenter();
        $delivery       = $this->getCurrentDelivery(false);
        $sessions       = $this->getRequestParameter('session');
        $requestOptions = $this->getRequestOptions([
            'sortby'      => 'timestamp',
            'sortorder'   => 'desc',
            'periodStart' => '',
            'periodEnd' => '',
            'detailed' => false
        ]);

        if (!is_array($sessions)) {
            $sessions = $sessions ? explode(',', $sessions) : [];
        }

        $breadcrumbs = [
            BreadcrumbsHelper::testCenters(),
            BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
            BreadcrumbsHelper::deliveries($testCenter, [
                    BreadcrumbsHelper::diagnostics($testCenter),
            ])
        ];

        $viewData = [
            'testCenter'  => $testCenter->getUri(),
            'set'         => TestCenterHelper::getSessionHistory($sessions, true, $requestOptions),
            'sessions'    => $sessions,
            'sortBy'      => $requestOptions['sortBy'],
            'sortOrder'   => $requestOptions['sortOrder'],
            'periodStart' => $requestOptions['periodStart'],
            'periodEnd'   => $requestOptions['periodEnd'],
        ];

        if ($delivery) {
            $breadcrumbs[] = BreadcrumbsHelper::deliveryMonitoring($testCenter, $delivery, DeliveryHelper::getDeliveries($testCenter));
            $viewData['delivery'] = $delivery->getUri();
        } else {
            $breadcrumbs[] = BreadcrumbsHelper::deliveryMonitoringAll($testCenter, DeliveryHelper::getDeliveries($testCenter));
        }

        $breadcrumbs[] = BreadcrumbsHelper::sessionHistory($testCenter, $delivery, $sessions);

        if (count($sessions) > 1) {
            $title = __('Detailed Session History of a selection');
        } else {
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($sessions[0]);
            $title = __('Detailed Session History of %s', $deliveryExecution->getLabel());
        }

        $this->setData('title', $title);
        $this->composeView('session-history', $viewData, $breadcrumbs);
    }

    /**
     * Display the activity reporting of the current test center
     */
    public function history()
    {
        try {
            $sessions       = $this->getRequestParameter('session');
            $requestOptions = $this->getRequestOptions([
                'sortby'      => 'timestamp',
                'sortorder'   => 'desc',
                'periodStart' => '',
                'periodEnd' => '',
            ]);

            if (!is_array($sessions)) {
                $sessions = $sessions ? explode(',', $sessions) : [];
            }
            $this->returnJson(TestCenterHelper::getSessionHistory($sessions, false, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No history service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }

    /**
     * Render page with assessment(s) result.
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function printReport()
    {
        if (!$this->hasRequestParameter('id')) {
            throw new \common_exception_MissingParameter('id');
        }
        $idList = $this->getRequestParameter('id');
        if (!is_array($idList)) {
            $idList = [$idList];
        }
        $result = [];

        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $currentUser = \common_session_SessionManager::getSession()->getUser();
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        /** @var $assessmentResultsService \oat\taoProctoring\model\AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get('taoProctoring/AssessmentResults');

        foreach ($idList as $deliveryExecutionId) {
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
            $delivery = $deliveryExecution->getDelivery();
            if (!isset($deliveries[$delivery->getUri()])) {
                \common_Logger::i('Attempt to print assessment results for which the proctor ' . $currentUser->getIdentifier() . ' has no access.');
                continue;
            }
            $deliveryData = $assessmentResultsService->getDeliveryData($deliveryExecution);
            if (!$deliveryData['end']) {
                continue;
            }
            $result[] = [
                'testTakerData' => $assessmentResultsService->getTestTakerData($deliveryExecution),
                'testData' => $assessmentResultsService->getTestData($deliveryExecution),
                'resultsData' => $assessmentResultsService->getResultsData($deliveryExecution),
                'deliveryData' => $deliveryData,
            ];
        }

        $this->setData('reports', $result);
        $this->setData('content-template', 'Reporting/print_report.tpl');
        $this->setView('Reporting/layout.tpl');
    }

    /**
     * Render printable rubrics
     *
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function printRubric()
    {
        if (!$this->hasRequestParameter('id')) {
            throw new \common_exception_MissingParameter('id');
        }
        $idList = $this->getRequestParameter('id');
        if (!is_array($idList)) {
            $idList = [$idList];
        }
        $result = [];

        /** @var $assessmentResultsService \oat\taoProctoring\model\AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get('taoProctoring/AssessmentResults');

        foreach ($idList as $deliveryExecutionId) {
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
            $deliveryData = $assessmentResultsService->getDeliveryData($deliveryExecution);
            if (!$deliveryData['end']) {
                continue;
            }
            $result[] = [
                'testData' => $assessmentResultsService->getTestData($deliveryExecution),
                'rubricContent' => $assessmentResultsService->getPrintableRubric($deliveryExecution),
                'testTakerData' => $assessmentResultsService->getTestTakerData($deliveryExecution),
                'deliveryData' => $deliveryData,
            ];
        }

        $this->setData('rubrics', $result);
        $this->setData('content-template', 'Reporting/print_rubric.tpl');
        $this->setView('Reporting/layout.tpl');
    }

    /**
     * Returns array of reports to datatable
     */
    public function reports(){
        $testCenter     = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();
        $this->returnJson(TestCenterHelper::getReports($testCenter, $requestOptions));
    }
}
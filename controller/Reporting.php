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
use oat\taoProctoring\helpers\TestCenter as TestCenterHelper;
use oat\taoProctoring\helpers\ReportingService;
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
     * Display the activity reporting of the current test center
     */
    public function index()
    {

        $testCenter     = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->setData('title', __('Assessment Activity Reporting for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'reporting-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => TestCenterHelper::getReports($testCenter, $requestOptions),
            ),
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
            Breadcrumbs::reporting(
                $testCenter,
                array(
                    Breadcrumbs::diagnostics($testCenter),
                    Breadcrumbs::deliveries($testCenter),
                )
            )
        ));
    }

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
            $result[] = [
                'testTakerData' => $assessmentResultsService->getTestTakerData($deliveryExecution),
                'testData' => $assessmentResultsService->getTestData($deliveryExecution),
                'resultsData' => $assessmentResultsService->getResultsData($deliveryExecution),
                'deliveryData' => $assessmentResultsService->getDeliveryData($deliveryExecution),
            ];
        }

        $this->setData('reports', $result);

        $this->setView('Reporting/print_report.tpl');
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
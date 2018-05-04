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

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\helpers\DataTableHelper;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\model\TestSessionHistoryService;

/**
 * Proctoring Reporting controllers for the assessment activity reporting screen.
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Reporting extends SimplePageModule
{
    use OntologyAwareTrait;

    /**
     * Gets the view parameters and data to display
     * @return array
     */
    protected function getViewData()
    {
        $delivery       = $this->hasRequestParameter('delivery')
            ? $this->getResource($this->getRequestParameter('delivery'))
            : null;
        $testCenter     = $this->hasRequestParameter('context')
            ? $this->getResource($this->getRequestParameter('context'))
            : null;
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

        // log access to history
        $deliveryLog = $this->getServiceManager()->get(DeliveryLog::SERVICE_ID);
        foreach ($sessions as $sessionUri) {
            $deliveryLog->log($sessionUri, 'HISTORY', []);
        }

        // retrieve history
        $historyService = $this->getServiceManager()->get(TestSessionHistoryService::SERVICE_ID);
        $history = DataTableHelper::paginate($historyService->getSessionsHistory($sessions, $requestOptions), $requestOptions);

        $viewData = [
            'set'         => $history,
            'sessions'    => $sessions,
            'sortBy'      => $requestOptions['sortBy'],
            'sortOrder'   => $requestOptions['sortOrder'],
            'periodStart' => $requestOptions['periodStart'],
            'periodEnd'   => $requestOptions['periodEnd'],
            'monitoringUrl' => $historyService->getBackUrl($delivery),
        ];

        if ($testCenter) {
            $viewData['monitoringUrl'] .= (strpos($viewData['monitoringUrl'], '?') === false) ? '?' : '&';
            $viewData['monitoringUrl'] .= 'context=' . urlencode($testCenter->getUri());
            $viewData['context'] = $testCenter->getUri();
        }

        if ($delivery) {
            $viewData['delivery'] = $delivery->getUri();
        }

        if (count($sessions) > 1) {
            $viewData['title'] = __('Detailed Session History of a selection');
        } else {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($sessions[0]);
            $viewData['title'] = __('Detailed Session History of %s', $deliveryExecution->getLabel());
        }
        
        return $viewData;
    }
    
    /**
     * Display the session history of the current test center
     */
    public function index()
    {
        $this->composeView('session-history', null, 'pages/index.tpl', 'tao');
    }
    
    /**
     * Display the session history of the current test center
     */
    public function sessionHistory()
    {
        $this->returnJson([
            'success' => true,
            'data' => $this->getViewData(),
        ]);
    }

    /**
     * Display the activity reporting of the current test center
     */
    public function history()
    {
        try {
            $sessions = $this->getRequestParameter('session');
            $requestOptions = $this->getRequestOptions([
                'sortby' => 'timestamp',
                'sortorder' => 'desc',
                'periodStart' => '',
                'periodEnd' => '',
            ]);

            if (!is_array($sessions)) {
                $sessions = $sessions ? explode(',', $sessions) : [];
            }
            $historyService = $this->getServiceManager()->get(TestSessionHistoryService::SERVICE_ID);
            $this->returnJson(DataTableHelper::paginate($historyService->getSessionsHistory($sessions, $requestOptions), $requestOptions));

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
        $context = $this->getRequestParameter('context');
        if (!is_array($idList)) {
            $idList = [$idList];
        }
        $result = [];

        /** @var ProctorService $deliveryService */
        $deliveryService = ServiceManager::getServiceManager()->get(ProctorService::SERVICE_ID);
        $currentUser = \common_session_SessionManager::getSession()->getUser();
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser, $context);

        /** @var $assessmentResultsService AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get(AssessmentResultsService::SERVICE_ID);

        foreach ($idList as $deliveryExecutionId) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
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

        /** @var $assessmentResultsService AssessmentResultsService */
        $assessmentResultsService = $this->getServiceManager()->get(AssessmentResultsService::SERVICE_ID);

        foreach ($idList as $deliveryExecutionId) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
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
}

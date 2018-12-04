<?php
/**
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
use oat\oatbox\service\ServiceNotFoundException;
use oat\tao\model\accessControl\AclProxy;
use oat\tao\model\mvc\DefaultUrlService;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\taoProctoring\model\datatable\DeliveriesMonitorDatatable;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\GuiSettingsService;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoProctoring\model\TestSessionHistoryService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;

/**
 * Monitoring Delivery controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Monitor extends SimplePageModule
{
    use OntologyAwareTrait;

    const ERROR_AUTHORIZE_EXECUTIONS = 1;
    const ERROR_PAUSE_EXECUTIONS = 2;
    const ERROR_TERMINATE_EXECUTIONS = 3;
    const ERROR_REPORT_IRREGULARITIES = 4;
    const ERROR_SET_EXTRA_TIME = 5;

    /**
     * Returns the currently proctored delivery
     *
     * @return \core_kernel_classes_Resource
     */
    protected function getCurrentDelivery()
    {
        return $this->hasRequestParameter('delivery')
            ? $this->getResource($this->getRequestParameter('delivery'))
            : null;
    }

    /**
     * Gets the view parameters and data to display
     * @return array
     * @throws \common_exception_Error
     */
    protected function getViewData()
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        $hasAccessToReactivate = AclProxy::hasAccess($user, MonitorProctorAdministrator::class, 'reactivateExecutions', array());
        $delivery = $this->getCurrentDelivery();
        $guiSettingsService = $this->getServiceLocator()->get(GuiSettingsService::SERVICE_ID);
        $assessmentResultsService = $this->getServiceLocator()->get(AssessmentResultsService::SERVICE_ID);
        $data = [
            'ismanageable' => false,
            'set' => [],
            'extrafields' => DeliveryHelper::getExtraFields(),
            'categories' => DeliveryHelper::getAllReasonsCategories($hasAccessToReactivate),
            'printReportButton' => $assessmentResultsService->getOption(AssessmentResultsService::OPTION_PRINT_REPORT_BUTTON),
            'printReportUrl' => $assessmentResultsService->getScoreReportUrlParts(),
            'timeHandling' => $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID)->getOption(DeliveryExecutionStateService::OPTION_TIME_HANDLING),
            'historyUrl' => $this->getServiceLocator()->get(TestSessionHistoryService::SERVICE_ID)->getHistoryUrl($delivery),
            'refreshBtn' => $guiSettingsService->getOption(GuiSettingsService::PROCTORING_REFRESH_BUTTON),
            'autoRefresh' => $guiSettingsService->getOption(GuiSettingsService::PROCTORING_AUTO_REFRESH),
            'canPause' => $guiSettingsService->getOption(GuiSettingsService::PROCTORING_ALLOW_PAUSE),
            'onlineStatus' => $this->getServiceLocator()->get(TestSessionConnectivityStatusService::SERVICE_ID)->hasOnlineMode(),
            'hasAccessToReactivate' => $hasAccessToReactivate
        ];

        if (!is_null($delivery)) {
            $data['delivery'] = $delivery->getUri();
        }
        if ($this->hasRequestParameter('context')) {
            $data['context'] = $this->getRequestParameter('context');
        }

        return $data;
    }

    /**
     * Monitoring view of a selected delivery
     */
    public function index()
    {
        $this->setData('homeUrl', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringHome'));
        $this->setData('logout', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringLogout'));
        $this->composeView('delivery-monitoring', null, 'pages/index.tpl', 'tao');
    }

    /**
     * Lists all available deliveries
     */
    public function monitor()
    {
        $this->returnJson([
            'success' => true,
            'data' => $this->getViewData(),
        ]);
    }

    /**
     * Gets the list of current executions for a delivery
     *
     * @throws \common_Exception
     */
    public function deliveryExecutions()
    {
        $dataTable = new DeliveriesMonitorDatatable($this->getCurrentDelivery(), $this->getRequest());
        $this->getServiceManager()->propagate($dataTable);
        $this->returnJson($dataTable);
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

            $data = DeliveryHelper::authoriseExecutions($deliveryExecution, $reason, $testCenter);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_AUTHORIZE_EXECUTIONS;
                $response['errorMsg'] = __('Some delivery executions have not been authorized');
            }

            $this->returnJson($response);
        } catch (QtiTestExtractionFailedException $e) {
            $response = [
                'success' => false,
                'data' => [],
                'errorCode' => self::ERROR_AUTHORIZE_EXECUTIONS,
                'errorMsg' => __('Decryption failed because of using the wrong customer app key.'),
            ];

            $this->returnJson($response);
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
            $data = DeliveryHelper::terminateExecutions($deliveryExecution, $reason);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_TERMINATE_EXECUTIONS;
                $response['errorMsg'] = __('Some delivery executions have not been terminated');
            }

            $this->returnJson($response);
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
            $data = DeliveryHelper::pauseExecutions($deliveryExecution, $reason);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_PAUSE_EXECUTIONS;
                $response['errorMsg'] = __('Some delivery executions have not been paused');
            }

            $this->returnJson($response);
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
            $data = DeliveryHelper::reportExecutions($deliveryExecution, $reason);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_REPORT_IRREGULARITIES;
                $response['errorMsg'] = __('Some delivery executions have not been reported');
            }

            $this->returnJson($response);
        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }

    /**
     * Extra Time handling: add or remove time on delivery executions
     *
     * @throws \common_Exception
     */
    public function extraTime()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $extraTime = floatval($this->getRequestParameter('time'));

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {
            $deliveryExecutionManagerService = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);
            $data = $deliveryExecutionManagerService->setExtraTime($deliveryExecution, $extraTime);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_SET_EXTRA_TIME;
                $response['errorMsg'] = __('Some delivery executions have not been updated');
            }

            $this->returnJson($response);
        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }
}

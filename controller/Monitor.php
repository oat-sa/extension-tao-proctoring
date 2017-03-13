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
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\model\TestSessionHistoryService;

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
     */
    protected function getViewData()
    {
        $service = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $delivery = $this->getCurrentDelivery();
        $context = $this->hasRequestParameter('context') ? $this->getRequestParameter('context') : null;
        $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery, $context);
        $data = [
            'ismanageable' => false,
            'defaulttag' => $this->hasRequestParameter('defaulttag') ? $this->getRequestParameter('defaulttag') : '',
            'set' => DeliveryHelper::buildDeliveryExecutionData($executions),
            'extrafields' => DeliveryHelper::getExtraFields(),
            'categories' => DeliveryHelper::getAllReasonsCategories(),
            'printReportButton' => $this->getServiceManager()->get(AssessmentResultsService::SERVICE_ID)->getOption(AssessmentResultsService::OPTION_PRINT_REPORT_BUTTON),
            'timeHandling' => $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID)->getOption(DeliveryExecutionStateService::OPTION_TIME_HANDLING),
            'historyUrl' => $this->getServiceManager()->get(TestSessionHistoryService::SERVICE_ID)->getHistoryUrl($delivery),
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
        $requestOptions = $this->getRequestOptions(['sortby' => 'date', 'sortorder' => 'DESC']);
        $context = $this->hasRequestParameter('context') ? $this->getRequestParameter('context') : null;
        $filters = $this->getRequestParameter('filtercolumns');
        if ($filters !== null) {
            foreach ($filters as $filterKey => $filterVal) {
                if ($filterKey === 'start_time') {
                    $times = explode(' - ', $filterVal);
                    $from = \DateTime::createFromFormat('Y/m/d', $times[0]);
                    $from->setTime(0, 0, 0);
                    $options['filters'][] = ['start_time' => '>' . $from->getTimestamp()];
                    if (isset($times[1])) {
                        $to = \DateTime::createFromFormat('Y/m/d', $times[1]);
                        $to->setTime(23, 59, 59);
                        $options['filters'][] = ['start_time' => '<' . $to->getTimestamp()];
                    }
                } else {
                    $options['filters'][] = [$filterKey => $filterVal];
                }
            }
        }
        $options['order'] = $requestOptions['sortBy'] . ' ' . mb_strtoupper($requestOptions['sortOrder']);
        $service = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $delivery = $this->getCurrentDelivery();
        $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery, $context, $options);
        $this->returnJson(DeliveryHelper::buildDeliveryExecutionData($executions, $requestOptions));
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

            $response = [
                'success' => !count($notAuthorised),
                'data' => [
                    'processed' => $authorised,
                    'unprocessed' => $notAuthorised
                ]
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_AUTHORIZE_EXECUTIONS;
                $response['errorMsg'] = __('Some delivery executions have not been authorized');
            }

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

            $terminated = DeliveryHelper::terminateExecutions($deliveryExecution, $reason);
            $notTerminated = array_diff($deliveryExecution, $terminated);

            $response = [
                'success' => !count($notTerminated),
                'data' => [
                    'processed' => $terminated,
                    'unprocessed' => $notTerminated
                ]
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

            $paused = DeliveryHelper::pauseExecutions($deliveryExecution, $reason);
            $notPaused = array_diff($deliveryExecution, $paused);

            $response = [
                'success' => !count($notPaused),
                'data' => [
                    'processed' => $paused,
                    'unprocessed' => $notPaused
                ]
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

            $reported = DeliveryHelper::reportExecutions($deliveryExecution, $reason);
            $notReported = array_diff($deliveryExecution, $reported);

            $response = [
                'success' => !count($notReported),
                'data' => [
                    'processed' => $reported,
                    'unprocessed' => $notReported
                ]
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

            $reported = DeliveryHelper::setExtraTime($deliveryExecution, $extraTime);
            $notReported = array_diff($deliveryExecution, $reported);

            $response = [
                'success' => !count($notReported),
                'data' => [
                    'processed' => $reported,
                    'unprocessed' => $notReported
                ]
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

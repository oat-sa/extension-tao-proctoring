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

use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\delivery\DeliveryService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\ReasonCategoryService;

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
    
    /**
     * Displays the index page of the deliveries list all available deliveries for the current test center
     */
    public function index()
    {
        $service = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $delivery = $this->getResource($this->getRequestParameter('delivery'));
        $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery);
        $this->composeView(
            'delivery-monitoring',
            array(
                'ismanageable' => false,
                'delivery' => $delivery->getUri(),
                'set' => DeliveryHelper::buildDeliveryExecutionData($executions),
                'extrafields' => DeliveryHelper::getExtraFields(),
                'categories' => $this->getAllReasonsCategories(),
                'printReportButton' => json_encode(false),
                'timeHandling' => json_encode(false),
            ),
            array(
            ),
            'Monitoring/index.tpl'
        );
    }
    
    /**
     * Gets the list of current executions for a delivery
     *
     * @throws \common_Exception
     */
    public function deliveryExecutions()
    {
        $service = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $delivery = $this->getResource($this->getRequestParameter('delivery'));
        $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery);
        $this->returnJson(DeliveryHelper::buildDeliveryExecutionData($executions));
    }

    /**
     * Get the list of all available categories, sorted by action names
     *
     * @return array
     */
    protected function getAllReasonsCategories(){
        /** @var ReasonCategoryService $categoryService */
        $categoryService = $this->getServiceManager()->get(ReasonCategoryService::SERVICE_ID);
    
        return array(
            'authorize' => array(),
            'pause' => $categoryService->getIrregularities(),
            'terminate' => $categoryService->getIrregularities(),
            'report' => $categoryService->getIrregularities(),
            'print' => [],
        );
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

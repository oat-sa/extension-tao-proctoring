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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */
namespace oat\taoProctoring\controller;

use common_Logger;
use common_session_SessionManager;
use oat\taoDelivery\controller\DeliveryServer as DefaultDeliveryServer;
use oat\taoDelivery\model\authorization\DeliveryAuthorizationProvider;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution;

/**
 * Override the default DeliveryServer Controller
 *
 * @package taoProctoring
 */
class DeliveryServer extends DefaultDeliveryServer
{

    /**
     * constructor: initialize the service and the default data
     * @return DeliveryServer
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Overrides the content extension data
     * @see {@link \taoDelivery_actions_DeliveryServer}
     */
    public function index()
    {
        // if the test taker passes by this page, he/she cannot access to any delivery without proctor authorization,
        // whatever the delivery execution status is.
        $deliveryExecutionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();
        $userUri = common_session_SessionManager::getSession()->getUserUri();
        $startedExecutions = array_merge(
            $deliveryExecutionService->getActiveDeliveryExecutions($userUri),
            $deliveryExecutionService->getPausedDeliveryExecutions($userUri),
            $deliveryExecutionService->getDeliveryExecutionsByStatus($userUri, DeliveryExecution::STATE_AWAITING),
            $deliveryExecutionService->getDeliveryExecutionsByStatus($userUri, DeliveryExecution::STATE_AUTHORIZED)
        );
        foreach($startedExecutions as $startedExecution) {
            if($startedExecution->getDelivery()->exists()) {
                $this->revoke($startedExecution);
            }
        }

        parent::index();
    }

    /**
     * Overrides the return URL
     * @return string the URL
     */
    protected function getReturnUrl()
    {
        return _url('index', 'DeliveryServer', 'taoProctoring');
    }

    /**
     * Displays the execution screen
     *
     * FIXME all state management must be centralized into a service, 
      * it should'nt be on the controller.
     *
     * @throws common_exception_Error
     */
    public function runDeliveryExecution() 
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        // ok, the delivery execution can be processed
        parent::runDeliveryExecution();
        $deliveryExecutionStateService->resumeExecution($deliveryExecution);
    }

    /**
     * The awaiting authorization screen
     */
    public function awaitingAuthorization() 
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $executionState = $deliveryExecutionStateService->getState($deliveryExecution);

        // if the test taker is already authorized, straight forward to the execution
        if (DeliveryExecution::STATE_AUTHORIZED === $executionState) {
            return $this->redirect(_url('runDeliveryExecution', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier())));
        }

        // if the test is in progress, first pause it to avoid inconsistent storage state
        if (DeliveryExecution::STATE_ACTIVE == $executionState) {
            $deliveryExecutionStateService->pauseExecution($deliveryExecution);
        }

        // we need to change the state of the delivery execution
        if (!in_array($executionState , array(DeliveryExecution::STATE_FINISHED, DeliveryExecution::STATE_TERMINATED))) {
            $deliveryExecutionStateService->waitExecution($deliveryExecution);
            $this->setData('deliveryExecution', $deliveryExecution->getIdentifier());
            $this->setData('deliveryLabel', $deliveryExecution->getLabel());
            $this->setData('init', !!$this->getRequestParameter('init'));
            $this->setData('returnUrl', $this->getReturnUrl());
            $this->setData('userLabel', common_session_SessionManager::getSession()->getUserLabel());
            $this->setData('client_config_url', $this->getClientConfigUrl());
            $this->setData('showControls', true);

            //set template
            $this->setData('content-template', 'DeliveryServer/awaiting.tpl');
            $this->setData('content-extension', 'taoProctoring');
            $this->setView('DeliveryServer/layout.tpl', 'taoDelivery');
        } else {
            // inconsistent state
            common_Logger::i(get_called_class() . '::awaitingAuthorization(): cannot wait authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->redirect(_url('index'));
        }
    }
    
    /**
     * The action called to check if the requested delivery execution has been authorized by the proctor
     */
    public function isAuthorized()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $executionState = $deliveryExecutionStateService->getState($deliveryExecution);

        $authorized = false;
        $success = true;
        $message = null;
        
        // reacts to a few particular states
        switch ($executionState) {
            case DeliveryExecution::STATE_AUTHORIZED:
                    $authorized = true;
                break;
            
            case DeliveryExecution::STATE_TERMINATED:
            case DeliveryExecution::STATE_FINISHED:
                $success = false;
                $message = __('This test has been terminated');
                break;
                
            case DeliveryExecution::STATE_PAUSED:
                $success = false;
                $message = __('This test has been suspended');
                break;
        }

        $this->returnJson(array(
            'authorized' => $authorized,
            'success' => $success,
            'message' => $message
        ));
    }
    

    protected function revoke(DeliveryExecution $deliveryExecution)
    {
        if($deliveryExecution->getState()->getUri() != DeliveryExecution::STATE_PAUSED){
            /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            $deliveryExecutionStateService->pauseExecution(
                $deliveryExecution,
                [
                    'reasons' => ['category' => 'focus-loss'],
                    'comment' => __('Assessment has been paused due to attempt to switch to another window/tab.'),
                ]
            );
        }
    }

}

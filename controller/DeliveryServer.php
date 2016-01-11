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

use oat\taoProctoring\model\implementation\DeliveryService;
use PHPSession;
use common_Logger;
use common_session_SessionManager;
use oat\taoDelivery\controller\DeliveryServer as DefaultDeliveryServer;
use oat\taoProctoring\model\DeliveryAuthorizationService;

/**
 * Override the default DeliveryServer Controller
 *
 * @package taoProctoring
 */
class DeliveryServer extends DefaultDeliveryServer
{

    /** @var DeliveryAuthorizationService */
    public $authorizationService;

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
        parent::index();

        // if the test taker passes by this page, he/she cannot access to any delivery without proctor authorization,
        // whatever the delivery execution status is.
        $deliveryExecutionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();
        $userUri = common_session_SessionManager::getSession()->getUserUri();
        $startedExecutions = array_merge(
            $deliveryExecutionService->getActiveDeliveryExecutions($userUri),
            $deliveryExecutionService->getPausedDeliveryExecutions($userUri)
        );
        foreach($startedExecutions as $startedExecution) {
            $this->getAuthorizationService()->revokeAuthorization($startedExecution);
        }
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
     * Overwrites the parent initDeliveryExecution()
     * Redirects the test taker to the awaitingAuthorization page after delivery initialization
     */
    public function initDeliveryExecution() 
    {
        // from this page the test taker can only goes to the awaiting page, so always revoke authorization
        $deliveryExecution = $this->_initDeliveryExecution();

        $this->getAuthorizationService()->revokeAuthorization($deliveryExecution);
	    $this->redirect(_url('awaitingAuthorization', null, null, array('init' => true, 'deliveryExecution' => $deliveryExecution->getIdentifier())));
	}

    /**
     * Displays the execution screen
     *
     * @throws common_exception_Error
     */
    public function runDeliveryExecution() 
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);
        
        if (DeliveryService::STATE_AUTHORIZED == $executionState && $this->getAuthorizationService()->isAuthorized($deliveryExecution)) {
            // the test taker is authorized to run the delivery
            // but a change is needed to make the delivery execution processable
            $deliveryService->resumeExecution($deliveryExecution);
            $executionState = $deliveryService->getState($deliveryExecution);
        }

        if (DeliveryService::STATE_INPROGRESS != $executionState ||
            (DeliveryService::STATE_INPROGRESS == $executionState && !$this->getAuthorizationService()->isAuthorized($deliveryExecution))) {
            // the test taker is not allowed to run the delivery
            // so we redirect him/her to the awaiting page
            common_Logger::i(get_called_class() . '::runDeliveryExecution(): try to run delivery without proctor authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->redirect(_url('awaitingAuthorization', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier())));
        }

        // ensure the result server object is properly set to avoid test runner issue 
        $this->ensureResultServerObject($deliveryExecution);

        // ok, the delivery execution can be processed
        parent::runDeliveryExecution();
    }
    
    /**
     * The awaiting authorization screen
     */
    public function awaitingAuthorization() 
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);

        // if the test taker is already authorized, straight forward to the execution
        if (DeliveryService::STATE_AUTHORIZED == $executionState) {
            $this->getAuthorizationService()->grantAuthorization($deliveryExecution);
            return $this->redirect(_url('runDeliveryExecution', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier())));
        }

        // from this page the test taker must wait for proctor authorization
        $this->getAuthorizationService()->revokeAuthorization($deliveryExecution);

        // if the test is in progress, first pause it to avoid inconsistent storage state
        if (DeliveryService::STATE_INPROGRESS == $executionState) {
            $deliveryService->pauseExecution($deliveryExecution);
        }

        // we need to change the state of the delivery execution
        if (DeliveryService::STATE_TERMINATED != $executionState && DeliveryService::STATE_COMPLETED != $executionState) {
            $deliveryService->waitExecution($deliveryExecution);
            $executionState = $deliveryService->getState($deliveryExecution);
        }

        if (DeliveryService::STATE_AWAITING == $executionState) {
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
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);

        $authorized = false;
        $success = true;
        $message = null;
        
        // reacts to a few particular states
        switch ($executionState) {
            case DeliveryService::STATE_AUTHORIZED:
                $this->getAuthorizationService()->grantAuthorization($deliveryExecution);
                $authorized = true;
                break;
            
            case DeliveryService::STATE_TERMINATED:
            case DeliveryService::STATE_COMPLETED:
                $success = false;
                $message = __('This test has been terminated');
                break;
                
            case DeliveryService::STATE_PAUSED:
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

    /**
     * Ensures the result server object is properly set
     * 
     * @param \taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution
     */
    protected function ensureResultServerObject($deliveryExecution)
    {
        $session = PHPSession::singleton();
        if (!$session->hasAttribute('resultServerObject') || !$session->getAttribute('resultServerObject')) {
            $compiledDelivery = $deliveryExecution->getDelivery();
            $resultServerUri = $compiledDelivery->getOnePropertyValue(new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
            $resultServerObject = new \taoResultServer_models_classes_ResultServer($resultServerUri, array());

            $session->setAttribute('resultServerUri', $resultServerUri->getUri());
            $session->setAttribute('resultServerObject', array($resultServerUri->getUri() => $resultServerObject));
        }
    }

    /**
     * @return DeliveryAuthorizationService
     */
    protected function getAuthorizationService()
    {
        if ($this->authorizationService === null) {
            $this->authorizationService = $this->getServiceManager()->get(DeliveryAuthorizationService::SERVICE_ID);
        }
        return $this->authorizationService;
    }
}

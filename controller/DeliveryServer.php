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
use \common_session_SessionManager;

/**
 * Override the default DeliveryServer Controller
 *
 * @package taoProctoring
 */
class DeliveryServer extends \taoDelivery_actions_DeliveryServer
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
     * Override the content extension data
     * @see {@link \taoDelivery_actions_DeliveryServer}
     */
    public function index()
    {
        parent::index();
        $this->setData('content-extension', 'taoProctoring');
    }

    /**
     * Override the return URL
     * @return string the URL
     */
    protected function getReturnUrl()
    {
        return _url('index', 'DeliveryServer', 'taoProctoring');
    }
    
    /**
     * Overwrite the parent initDeliveryExecution()
     * Forward the test taker to the awaitingAuthorization page after delivery initialization
     */
    public function initDeliveryExecution() {
        $deliveryExecution = $this->_initDeliveryExecution();
	    $this->redirect(_url('awaitingAuthorization', null, null, array('init' => true, 'deliveryExecution' => $deliveryExecution->getIdentifier())));
	}

    /**
     * Displays the execution screen
     *
     * @throws common_exception_Error
     */
    public function runDeliveryExecution() {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);

        if (DeliveryService::STATE_AUTHORIZED == $executionState) {
            // the test taker is authorized to run the delivery
            // but a change is needed to make the delivery execution processable
            $deliveryService->resumeExecution($deliveryExecution);
            $executionState = $deliveryService->getState($deliveryExecution);
        }

        if (DeliveryService::STATE_INPROGRESS != $executionState) {
            // the test taker is not allowed to run the delivery
            // so we redirect him/her to the awaiting page
            \common_Logger::i(get_called_class() . '::runDeliveryExecution(): try to run delivery without proctor authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->forward('awaitingAuthorization');
        }

        // ok, the delivery execution can be processed
        parent::runDeliveryExecution();
    }
    
    /**
     * The awaiting authorization screen
     */
    public function awaitingAuthorization() {

        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);

        // if the test taker is already authorized, straight forward to the execution
        if (DeliveryService::STATE_AUTHORIZED == $executionState || DeliveryService::STATE_INPROGRESS == $executionState) {
            return $this->forward('runDeliveryExecution');
        }

        // we need to change the state of the delivery execution
        if (DeliveryService::STATE_INIT == $executionState || DeliveryService::STATE_PAUSED == $executionState) {
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
            \common_Logger::i(get_called_class() . '::awaitingAuthorization(): cannot wait authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->forward('index');
        }
    }
    
    /**
     * The action called to check if the requested delivery execution has been authorized by the proctor
     */
    public function isAuthorized(){
        
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $executionState = $deliveryService->getState($deliveryExecution);

        $authorized = DeliveryService::STATE_AUTHORIZED == $executionState || DeliveryService::STATE_INPROGRESS == $executionState;
        $success = true;
        $message = null;

        if (DeliveryService::STATE_TERMINATED == $executionState || DeliveryService::STATE_COMPLETED == $executionState) {
            $success = false;
            $message = __('This test has been terminated');
        }

        if (DeliveryService::STATE_PAUSED == $executionState) {
            $success = false;
            $message = __('This test has been suspended');
        }

        $this->returnJson(array(
            'authorized' => $authorized,
            'success' => $success,
            'message' => $message
        ));
    }
}

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

/**
 * Override the default DeliveryServer Controller
 *
 * @package taoProctoring
 */
class DeliveryServer extends \taoDelivery_actions_DeliveryServer
{
    /**
     * The name of the secure key used to grant proctor authorisation.
     * If the secure key is not set, or its value is not the same with the access key, 
     * the test taker must wait for proctor authorization
     */
    const SECURE_KEY_NAME = 'proctor_secure_key';
    
    /**
     * The name of the access key used to grant proctor authorisation.
     * If the access key is not set, or its value is not the same with the secure key, 
     * the test taker must wait for proctor authorization
     */
    const ACCESS_KEY_NAME = 'proctor_access_key';

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
        $this->setData('content-extension', 'taoProctoring');
        
        // if the test taker passes by this page, he/she cannot access to any delivery without proctor authorization,
        // whatever the delivery execution status is.
        $this->revokeAuthorization();
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
        $this->revokeAuthorization();
        
        $deliveryExecution = $this->_initDeliveryExecution();
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
        
        if (DeliveryService::STATE_AUTHORIZED == $executionState && $this->checkAuthorization()) {
            // the test taker is authorized to run the delivery
            // but a change is needed to make the delivery execution processable
            $deliveryService->resumeExecution($deliveryExecution);
            $executionState = $deliveryService->getState($deliveryExecution);
        }

        if (DeliveryService::STATE_INPROGRESS != $executionState ||
            (DeliveryService::STATE_INPROGRESS == $executionState && !$this->checkAuthorization())) {
            // the test taker is not allowed to run the delivery
            // so we redirect him/her to the awaiting page
            common_Logger::i(get_called_class() . '::runDeliveryExecution(): try to run delivery without proctor authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->redirect(_url('awaitingAuthorization', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier())));
        }

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
        // note: the authorized state is valid only if the security key has been set,
        // if the test taker tries to directly access this page, the security key may not be initialized (i.e. just logged in)
        if (DeliveryService::STATE_AUTHORIZED == $executionState && $this->hasSecurityKey()) {
            $this->grantAuthorization();
            return $this->redirect(_url('runDeliveryExecution', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier())));
        }

        // from this page the test taker must wait for proctor authorization
        $this->revokeAuthorization();

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
                // note: the authorized state is valid only if the security key has been set,
                // if the test taker tries to directly access this page, the security key may not be initialized (i.e. just logged in)
                if ($this->hasSecurityKey()) {
                    $this->grantAuthorization();
                    $authorized = true;
                }
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
     * Checks if a security key has been set.
     * @return bool
     */
    protected function hasSecurityKey()
    {
        return PHPSession::singleton()->hasAttribute(self::SECURE_KEY_NAME);
    }
    
    /**
     * Gets the current security key.
     * Generates a new one if needed.
     * @return string
     */
    protected function getSecurityKey()
    {
        if (!$this->hasSecurityKey()) {
            $this->revokeAuthorization();
        }
        return PHPSession::singleton()->getAttribute(self::SECURE_KEY_NAME);
    }

    /**
     * Grants the proctor authorization: sets the current security key into the access key.
     */
    protected function grantAuthorization()
    {
        $securityKey = $this->getSecurityKey();
        common_Logger::i('Grant the proctor authorization, with security key: ' . $securityKey);
        PHPSession::singleton()->setAttribute(self::ACCESS_KEY_NAME, $securityKey);
    }

    /**
     * Revokes the proctor authorization: generates a new security key.
     */
    protected function revokeAuthorization()
    {
        $session = PHPSession::singleton();
        $securityKey = uniqid();
        common_Logger::i('Reset the proctor security key with value: ' . $securityKey);
        $session->setAttribute(self::SECURE_KEY_NAME, $securityKey);
        $session->setAttribute(self::ACCESS_KEY_NAME, null);
    }

    /**
     * Checks the proctor authorization: checks if the value of the access key is the same as the security key.
     * @return bool
     */
    protected function checkAuthorization()
    {
        $session = PHPSession::singleton();
        return $session->hasAttribute(self::ACCESS_KEY_NAME) && 
               $session->getAttribute(self::ACCESS_KEY_NAME) == $this->getSecurityKey();
    }
}

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
    
    public function initDeliveryExecution() {
        $deliveryExecution = $this->_initDeliveryExecution();
	    $this->redirect(_url('awaitingAuthorization', null, null, array('init' => true, 'deliveryExecution' => $deliveryExecution->getIdentifier())));
	}
    
    public function awaitingAuthorization() {
        
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        
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
    }
    
    public function isAuthorized(){
        
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
        $authorized = ($deliveryService->getState($deliveryExecution) === DeliveryService::STATE_AUTHORIZED);
        $this->returnJson(array('authorized' => $authorized));
    }
}

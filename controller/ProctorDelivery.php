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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *               
 * 
 */

namespace oat\taoProctoring\controller;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class ProctorDelivery extends \tao_actions_CommonModule {

    /**
     * initialize the services
     */
    public function __construct(){
        parent::__construct();

        $this->defaultData();
        $this->setData('clientConfigUrl',$this->getClientConfigUrl());
    }

    /**
     * Views a delivery
     */
    public function index() {
        
        $deliveryId = $this->getRequestParameter('id');
        
        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
            $currentUser = \common_session_SessionManager::getSession()->getUser();
            
            $delivery = $deliveryService->getDelivery($deliveryId);

            if (!$delivery) {
                throw new Exception('Unknown delivery!');
            }
            
            $this->setData('breadcrumbs', array(
                array(
                    'id' => 'home',
                    'url' => _url('index', 'TaoProctoring'),
                    'label' => __('Home'),
                ),
                array(
                    'id' => 'manageDelivery',
                    'label' => __('Manage Delivery'),
                    'data' => $delivery->getLabel('label'),
                ),
            ));
            $this->setData('delivery', $delivery);
            $this->setData('clientConfigUrl',$this->getClientConfigUrl());
            
            $this->setView('ProctorDelivery/index.tpl');
        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }
    
    public function assign() {
        $deliveryId = $this->getRequestParameter('id');
        $testTaker = $this->getRequestParameter('tt');
    }
    
}
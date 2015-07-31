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

use oat\generisHard\models\hardapi\Exception;
use \common_session_SessionManager;
use oat\oatbox\service\ServiceNotFoundException;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class TaoProctoring extends \tao_actions_CommonModule {

    /**
     * initialize the services
     */
    public function __construct(){
        parent::__construct();

        $this->defaultData();
        $this->setData('clientConfigUrl',$this->getClientConfigUrl());
    }

    /**
     * A possible entry point to tao
     */
    public function index() {

        try {
        
            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
            $currentUser = \common_session_SessionManager::getSession()->getUser();
            
            $deliveries = $deliveryService->getProctorableDeliveries($currentUser);
            
            $this->setData('deliveries', $deliveries);

            $this->setView('TaoProctoring/index.tpl');
        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
        
    }

    /**
     * Just logout the user
     */
    public function logout(){
        \common_session_SessionManager::endSession();
        $this->redirect(ROOT_URL);
    }
}

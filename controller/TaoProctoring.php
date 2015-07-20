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
    }

    /**
     * Gets the list of available deliveries
     * @return array
     */
    private function getDeliveries() {
        /* --- Mock data --- */
        return array(
            'delivery-1' => array(
                'uri' => 'delivery-1',
                'label' => 'test A for classroom XYZ',
                'disabled' => false
            ),
            'delivery-2' => array(
                'uri' => 'delivery-2',
                'label' => 'test B for classroom XYZ',
                'disabled' => false
            ),
            'delivery-3' => array(
                'uri' => 'delivery-3',
                'label' => 'test C for classroom XYZ',
                'disabled' => true
            ),
        );
        /* --- End of mock data --- */
    }

    /**
     * Gets a delivery by its URI
     * @param string $uri
     * @return array
     */
    private function getDelivery($uri) {
        $deliveries = $this->getDeliveries();
        return isset($deliveries[$uri]) ? $deliveries[$uri] : null;
    }

    /**
     * A possible entry point to tao
     */
    public function index() {
        $deliveries = $this->getDeliveries();
        
        $this->setData('deliveries', $deliveries);
        
        $this->setView('TaoProctoring/index.tpl');
    }
    
    /**
     * Manage a delivery
     */
    public function delivery() {
        $uri = $this->getRequestParameter('uri');
        $delivery = $this->getDelivery($uri);
        
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
                'data' => $delivery['label'],
            ),
        ));
        $this->setData('delivery', $delivery);

        $this->setView('TaoProctoring/delivery.tpl');
    }

    /**
     * Just logout the user
     */
    public function logout(){
        \common_session_SessionManager::endSession();
        $this->redirect(ROOT_URL);
    }
}

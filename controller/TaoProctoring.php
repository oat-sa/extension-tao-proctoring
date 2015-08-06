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
     * Gets a list of available deliveries
     *
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    private function getDeliveries() {

        $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
        $currentUser = \common_session_SessionManager::getSession()->getUser();

        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        $entries = array();
        foreach ($deliveries as $delivery) {

            $entries[] = array(
                'url' => _url('index', 'ProctorDelivery', null, array('id' => $delivery->getId())),
                'label' => $delivery->getLabel(),
                'text' => __('Manage'),
            );

        }

        return $entries;

    }

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index() {

        try {

            $deliveries = $this->getDeliveries();

            $this->defaultData();
            $this->setData('clientConfigUrl', $this->getClientConfigUrl());
            $this->setData('deliveries', $deliveries);
            $this->setData('template', 'TaoProctoring/index.tpl');

            $this->setView('layout.tpl');

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the available deliveries using JSON format
     */
    public function deliveries() {

        try {

            $deliveries = $this->getDeliveries();

            $this->returnJson(array(
                'entries' => $deliveries
            ));

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

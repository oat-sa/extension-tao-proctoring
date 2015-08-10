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
     * Views a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function index() {
        
        $deliveryId = $this->getRequestParameter('id');
        
        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $delivery = $deliveryService->getDelivery($deliveryId);

            if (!$delivery) {
                throw new \Exception('Unknown delivery!');
            }

            $testTakers = $deliveryService->getDeliveryTestTakers($deliveryId, $this->getRequestOptions());

            $this->defaultData();
            $this->setData('clientConfigUrl', $this->getClientConfigUrl());
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
            $this->setData('testTakers', $testTakers);
            $this->setData('template', 'ProctorDelivery/index.tpl');

            $this->setView('layout.tpl');

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * List available test takers to assign to a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function testTakers() {

        $deliveryId = $this->getRequestParameter('id');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
            $currentUser = \common_session_SessionManager::getSession()->getUser();

            $delivery = $deliveryService->getDelivery($deliveryId);

            if (!$delivery) {
                throw new \Exception('Unknown delivery!');
            }

            $testTakers = $deliveryService->getAvailableTestTakers($currentUser, $deliveryId, $this->getRequestOptions());

            $this->defaultData();
            $this->setData('clientConfigUrl', $this->getClientConfigUrl());
            $this->setData('breadcrumbs', array(
                array(
                    'id' => 'home',
                    'url' => _url('index', 'TaoProctoring'),
                    'label' => __('Home'),
                ),
                array(
                    'id' => 'manageDelivery',
                    'url' => _url('index', 'ProctorDelivery', null, array('id' => $deliveryId)),
                    'label' => __('Manage Delivery'),
                    'data' => $delivery->getLabel('label'),
                ),
                array(
                    'id' => 'assign',
                    'label' => __('Assign test takers'),
                ),
            ));
            $this->setData('delivery', $delivery);
            $this->setData('testTakers', $testTakers);
            $this->setData('template', 'ProctorDelivery/testTakers.tpl');

            $this->setView('layout.tpl');

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the request options
     *
     * @return array
     */
    private function getRequestOptions() {

        $page = $this->hasRequestParameter('page') ? $this->getRequestParameter('page') : 1;
        $rows = $this->hasRequestParameter('rows') ? $this->getRequestParameter('rows') : 15;
        $sortBy = $this->hasRequestParameter('sortby') ? $this->getRequestParameter('sortby') : 'firstname';
        $sortOrder = $this->hasRequestParameter('sortorder') ? $this->getRequestParameter('sortorder') : 'asc';
        $filter = $this->hasRequestParameter('filter') ? $this->getRequestParameter('filter') : null;

        return array(
            'page' => $page,
            'rows' => $rows,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filter' => $filter,
        );

    }

    /**
     * Gets the list of test takers assigned to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function deliveryTestTakers() {

        $deliveryId = $this->getRequestParameter('id');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $testTakers = $deliveryService->getDeliveryTestTakers($deliveryId, $this->getRequestOptions());

            $this->returnJson($testTakers);

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Gets the list of test takers available for the proctor
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function availableTestTakers() {

        $deliveryId = $this->getRequestParameter('id');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $proctor = \common_session_SessionManager::getSession()->getUser();
            $testTakers = $deliveryService->getAvailableTestTakers($proctor, $deliveryId, $this->getRequestOptions());

            $this->returnJson($testTakers);

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Assign a test taker to a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function assign() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = $deliveryService->assignTestTaker($testTakerId, $deliveryId);

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }
    
}
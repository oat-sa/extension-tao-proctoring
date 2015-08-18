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

use oat\oatbox\user\User;

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

            $requestOptions = $this->getRequestOptions();
            $users = $deliveryService->getDeliveryTestTakers($deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

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

            $requestOptions = $this->getRequestOptions();
            $users = $deliveryService->getAvailableTestTakers($currentUser, $deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

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
     * Gets the value of a string property from a user
     * @param User $user
     * @param string $property
     * @return mixed|string
     */
    private function getUserStringProp($user, $property) {
        $value = $user->getPropertyValues($property);
        return empty($value) ? '' : current($value);
    }

    /**
     * Get a list of test takers usable in a table page
     * @param User[] $users
     * @param array $options
     * @return array
     */
    private function getTestTakersPage($users, $options) {
        $amount = count($users);
        $rows = max(1, abs(ceil(isset($options['rows']) ? $options['rows'] : 25)));
        $total = ceil($amount / $rows);
        $page = max(1, floor(min(isset($options['page']) ? $options['page'] : 1, $total)));
        $start = ($page - 1) * $rows;
        $list = array();

        $users = array_slice($users, ($page - 1) * $rows, $rows);

        foreach($users as $user) {
            /* @var $user User */
            $firstName = $this->getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = $this->getUserStringProp($user, PROPERTY_USER_LASTNAME);
            if (empty($firstName) && empty($lastName)) {
                $firstName = $this->getUserStringProp($user, RDFS_LABEL);
            }

            $list[] = array(
                'id' => $user->getIdentifier(),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'company' => '',
                'status' => ''
            );
        }

        return array(
            'offset' => $start,
            'length' => count($list),
            'amount' => $amount,
            'total'  => $total,
            'page'   => $page,
            'rows'   => $rows,
            'data'   => $list
        );
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

            $requestOptions = $this->getRequestOptions();
            $users = $deliveryService->getDeliveryTestTakers($deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

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

            $requestOptions = $this->getRequestOptions();
            $proctor = \common_session_SessionManager::getSession()->getUser();
            $users = $deliveryService->getAvailableTestTakers($proctor, $deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

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

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->assignTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Authorise a test taker to run the delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function authorise() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->authoriseTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Remove a test taker from a delivery
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function remove() {

        $deliveryId = $this->getRequestParameter('id');
        $testTakerId = $this->getRequestParameter('tt');

        if (!is_array($testTakerId)) {
            $testTakerId = array($testTakerId);
        }

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $result = true;
            foreach($testTakerId as $ttId) {
                $result = $deliveryService->unassignTestTaker($ttId, $deliveryId) && $result;
            }

            $this->returnJson(array(
                'success' => $result
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

}
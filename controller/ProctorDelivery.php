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
     * Gets a list of available deliveries
     *
     * @param $testSiteId
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    private function getDeliveries($testSiteId) {

        $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
        $currentUser = \common_session_SessionManager::getSession()->getUser();

        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        $id = 'all';
        $entries = array(array(
            'id' => $id,
            'url' => _url('delivery', 'ProctorDelivery', null, array('id' => $id, 'testsite' => $testSiteId)),
            'label' => __('All Deliveries'),
            'text' => __('Manage'),
            'cls' => 'dark'
        ));
        foreach ($deliveries as $delivery) {
            $id = $delivery->getId();
            $entries[] = array(
                'id' => $id,
                'url' => _url('delivery', 'ProctorDelivery', null, array('id' => $id, 'testsite' => $testSiteId)),
                'label' => $delivery->getLabel(),
                'text' => __('Manage'),
            );

        }

        return $entries;

    }

    /**
     * Gets a list of available test sites
     *
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    private function getTestSites() {
        $testSiteService = $this->getServiceManager()->get('taoProctoring/testSite');

        return $testSiteService->getTestSites();
    }

    /**
     * Gets the breadcrumbs
     * @param string $testSiteId
     * @param \taoDelivery_models_classes_DeliveryRdf $delivery
     * @param array $extra
     * @return array
     */
    private function getBreadcrumbs($testSiteId, $delivery = null, $extra = array()) {
        $breadcrumbs = array(
            array(
                'id' => 'home',
                'url' => _url('index', 'TaoProctoring'),
                'label' => __('Test sites')
            ),
            array(
                'id' => $testSiteId,
                'url' => _url('testSite', 'TaoProctoring', null, array('id' => $testSiteId)),
                'label' => __('Test site %d', $testSiteId),
            ),
            array(
                'id' => 'deliveries',
                'url' => _url('index', 'ProctorDelivery', null, array('id' => $testSiteId)),
                'label' => __('Deliveries'),
                'entries' => array(
                    array(
                        'id' => 'diagnostic',
                        'url' => _url('diagnostic', 'TaoProctoring', null, array('id' => $testSiteId)),
                        'label' => __('Readiness Check'),
                    ),
                    array(
                        'id' => 'report',
                        'url' => _url('report', 'TaoProctoring', null, array('id' => $testSiteId)),
                        'label' => __('Assessment Activity Reporting'),
                    ),
                )
            ),
        );

        $otherTestSites = array_filter($this->getTestSites(), function($value) use ($testSiteId) {
            return $value['id'] != $testSiteId;
        });

        if (count($otherTestSites)) {
            $breadcrumbs[1]['entries'] = $otherTestSites;
        }

        if ($delivery) {
            $breadcrumbs[] = array(
                'id' => $delivery->getId(),
                'url' => _url('delivery', 'ProctorDelivery', null, array('id' => $delivery->getId(), 'testsite' => $testSiteId)),
                'label' => __('Manage Delivery'),
                'data' => $delivery->getLabel(),
            );

            $otherDeliveries = array_filter($this->getDeliveries($testSiteId), function($value) use ($delivery) {
                return $value['id'] != $delivery->getId();
            });

            if (count($otherDeliveries)) {
                $breadcrumbs[3]['entries'] = $otherDeliveries;
            }
        }

        if (count($extra)) {
            $breadcrumbs[] = $extra;
        }

        return $breadcrumbs;
    }

    /**
     * Sets the page using default behavior
     *
     * @param string $cls
     * @param array $data
     * @throws Exception
     */
    private function setPage($cls, $data) {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->returnJson($data);
        } else {
            $this->defaultData();
            $this->setData('clientConfigUrl', $this->getClientConfigUrl());
            $this->setData('cls', $cls);
            $this->setData('data', $data);

            $this->setView('layout.tpl');
        }
    }

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index() {

        try {

            $testSiteId = $this->getRequestParameter('id');
            $this->setPage('deliveries-listing', array(
                'list' => $this->getDeliveries($testSiteId),
                'breadcrumbs' => $this->getBreadcrumbs($testSiteId)
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Views a delivery
     *
     * @throws \Exception
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function delivery() {
        
        $deliveryId = $this->getRequestParameter('id');
        $testSiteId = $this->getRequestParameter('testsite');

        try {

            $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');

            $delivery = $deliveryService->getDelivery($deliveryId);

            if (!$delivery) {
                throw new \Exception('Unknown delivery!');
            }

            $requestOptions = $this->getRequestOptions();
            $users = $deliveryService->getDeliveryTestTakers($deliveryId, $requestOptions);
            $testTakers = $this->getTestTakersPage($users, $requestOptions);

            $this->setData('title', $delivery->getLabel());

            $this->setPage('delivery-manager', array(
                'id' => $delivery->getUri(),
                'testsite' => $testSiteId,
                'set' => $testTakers,
                'breadcrumbs' => $this->getBreadcrumbs($testSiteId, $delivery),

            ));

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
        $testSiteId = $this->getRequestParameter('testsite');

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

            $this->setData('title', __('Assign test takers to %s', $delivery->getLabel()));

            $this->setPage('assign-test-takers', array(
                'id' => $delivery->getUri(),
                'testsite' => $testSiteId,
                'set' => $testTakers,
                'breadcrumbs' => $this->getBreadcrumbs($testSiteId, $delivery, array(
                        'id' => 'assign',
                        'label' => __('Assign test takers'),
                )),
            ));

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
     * Paginates a list of items to render a data subset in a table
     * @param array $data
     * @param array $options
     * @return array
     */
    private function paginate($data, $options) {
        $amount = count($data);
        $rows = max(1, abs(ceil(isset($options['rows']) ? $options['rows'] : 25)));
        $total = ceil($amount / $rows);
        $page = max(1, floor(min(isset($options['page']) ? $options['page'] : 1, $total)));
        $start = ($page - 1) * $rows;
        $list = array();

        $data = array_slice($data, ($page - 1) * $rows, $rows);

        return array(
            'offset' => $start,
            'length' => count($list),
            'amount' => $amount,
            'total'  => $total,
            'page'   => $page,
            'rows'   => $rows,
            'data'   => $data
        );
    }

    /**
     * Get a list of test takers usable in a table page
     * @param User[] $users
     * @param array $options
     * @return array
     */
    private function getTestTakersPage($users, $options) {
        $page = $this->paginate($users, $options);

        $list = array();
        foreach($page['data'] as $user) {
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

        $page['data'] = $list;

        return $page;
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
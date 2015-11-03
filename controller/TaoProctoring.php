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
use oat\oatbox\user\User;

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
     * Gets the list of readiness checks related to a test site
     * 
     * @param $id
     * @return array
     */
    private function getReadinessChecks($id) {
        $count = 10;
        $os = array('WinXP', 'Win7', 'Win8', 'Win10', 'Linux', 'Mac OS X');
        $browser = array('IE11', 'Edge', 'Firefox', 'Chrome', 'Safari', 'Opera');
        $performances = array('bad', 'medium', 'good');
        $bandwidth = array('30', '50', '70', '>100');
        $date = array('2015-09-16 13:04', '2015-09-21 10:23', '2015-10-06 09:34', '2015-10-18 11:43', '2015-10-29 14:53');
        $results = array();
        
        for ($i = 0; $i < $count; $i ++) {
            $id = $i + 1;
            $results[] = array(
                'id' => $id,
                'workstation' => 'Computer ' . $id,
                'os' => $os[array_rand($os)],
                'browser' => $browser[array_rand($browser)],
                'performance' => $performances[array_rand($performances)],
                'bandwidth' => $bandwidth[array_rand($bandwidth)],
                'date' => $date[array_rand($date)],
            ); 
        }
        
        return $results;
    }

    /**
     * Gets the list of assessment reports related to a test site
     *
     * @param $id
     * @return array
     */
    private function getReports($id) {
        $count = 10;

        $deliveryService = $this->getServiceManager()->get('taoProctoring/delivery');
        $currentUser = \common_session_SessionManager::getSession()->getUser();
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);
        
        function getTestTakers($deliveryId, $deliveryService)  {
            static $cache = array();
            if (!isset($cache[$deliveryId])) {
                $cache[$deliveryId] = $deliveryService->getDeliveryTestTakers($deliveryId);
            }
            return $cache[$deliveryId];
        }

        function getUserStringProp($user, $property) {
            $value = $user->getPropertyValues($property);
            return empty($value) ? '' : current($value);
        }
        
        function getUserName($user) {
            $firstName = getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = getUserStringProp($user, PROPERTY_USER_LASTNAME);
            if (empty($firstName) && empty($lastName)) {
                $firstName = getUserStringProp($user, RDFS_LABEL);
            }
            
            return $firstName . ' ' . $lastName;
        }
        
        $status = array('Completed', 'Terminated', 'Pending', 'Paused', 'Running');
        $date = array('2015-09-16 13:04', '2015-09-21 10:23', '2015-10-06 09:34', '2015-10-18 11:43', '2015-10-29 14:53');
        $irregularity = array('', '', 'cell phone ringing', '', '', 'sickness break / restroom for 10 min', '', '');
        $breaks = array(0, 0, 1, 0, 0, 2, 0, 0, 3, 0, 0);
        $results = array();

        for ($i = 0; $i < $count; $i ++) {
            $id = $i + 1;
            
            $delivery = $deliveries[array_rand($deliveries)];
            $testTakers = getTestTakers($delivery->getId(), $deliveryService);
            $break = $breaks[array_rand($breaks)];
            
            $results[] = array(
                'id' => $id,
                'delivery' => $delivery->getLabel(),
                'testtaker' => getUserName($testTakers[array_rand($testTakers)]),
                'proctor' => getUserName($currentUser),
                'status' => $status[array_rand($status)],
                'start' => $date[array_rand($date)],
                'end' => $date[array_rand($date)],
                'pause' => $break,
                'resume' => $break,
                'irregularities' => $irregularity[array_rand($irregularity)],
            );
        }

        return $results;
    }
    
    /**
     * Gets a list of entries available for a test site
     *
     * @param $id
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    private function getTestSiteEntries($id) {
        
        $entries = array(
            array(
                'id' => 'diagnostic',
                'url' => _url('diagnostic', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Readiness Check'),
                'content' => __('Check the compatibility of the current workstation and see the results'),
                'text' => __('Go')
            ),
            array(
                'id' => 'deliveries',
                'url' => _url('index', 'ProctorDelivery', null, array('id' => $id)),
                'label' => __('Deliveries'),
                'content' => __('Monitor and manage the deliveries of the test site'),
                'text' => __('Go')
            ),
            array(
                'id' => 'report',
                'url' => _url('report', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Assessment Activity Reporting'),
                'content' => __('Generate and review test histories'),
                'text' => __('Go')
            ),
        );
        
        return $entries;
    }

    /**
     * Gets the breadcrumbs
     * @param string $id
     * @param array $extra
     * @return array
     */
    private function getBreadcrumbs($id = null, $extra = array()) {
        $breadcrumbs = array(
            array(
                'id' => 'home',
                'url' => _url('index', 'TaoProctoring'),
                'label' => __('Test sites')
            ),
        );
        
        if ($id) {
            $breadcrumbs[] = array(
                'id' => $id,
                'url' => _url('testSite', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Test site %d', $id),
            );

            $otherTestSites = array_filter($this->getTestSites(), function($value) use ($id) {
                return $value['id'] != $id;
            });

            if (count($otherTestSites)) {
                $breadcrumbs[1]['entries'] = $otherTestSites;
            }
        }
        
        if (is_string($extra)) {
            $entryId = $extra;
            $otherEntries = array();
            $extra = array();
            foreach($this->getTestSiteEntries($id) as $entry) {
                if ($entry['id'] == $entryId) {
                    $extra = $entry;
                } else {
                    $otherEntries[] = $entry;
                }
            }
            $extra['entries'] = $otherEntries;
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
     * Displays the index page of the extension: list all available test sites.
     */
    public function index() {

        try {

            $this->setPage('testsites-listing', array(
                'list' => $this->getTestSites(),
                'breadcrumbs' => $this->getBreadcrumbs(),
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No test site service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }
    
    /**
     * Displays the index page for a test site
     */
    public function testSite() {

        try {

            $id = $this->getRequestParameter('id');
            $this->setPage('testsite', array(
                'id' => $id,
                'title' => __('Test site %d', $id),
                'list' => $this->getTestSiteEntries($id),
                'breadcrumbs' => $this->getBreadcrumbs($id),
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No test site service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }
    
    /**
     * Displays the readiness check page
     */
    public function diagnostic() {

        try {

            $id = $this->getRequestParameter('id');
            $requestOptions = $this->getRequestOptions();
            $diagnostics = $this->getReadinessChecks($id);
            
            $this->setData('title', __('Readiness Check for test site %s', $id));
            $this->setPage('diagnostic', array(
                'id' => $id,
                'set' => $this->paginate($diagnostics, $requestOptions),
                'breadcrumbs' => $this->getBreadcrumbs($id, 'diagnostic'),
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No diagnostic service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }

    }

    /**
     * Displays the reporting page
     */
    public function report() {

        try {

            $id = $this->getRequestParameter('id');
            $requestOptions = $this->getRequestOptions();
            $reports = $this->getReports($id);

            $this->setData('title', __('Assessment Activity Reporting for test site %s', $id));
            $this->setPage('report', array(
                'id' => $id,
                'set' => $this->paginate($reports, $requestOptions),
                'breadcrumbs' => $this->getBreadcrumbs($id, 'report'),
            ));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No report service defined for proctoring');
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

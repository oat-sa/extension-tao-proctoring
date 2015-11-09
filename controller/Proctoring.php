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

use \common_session_SessionManager as SessionManager;
use \core_kernel_classes_Resource;

/**
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Proctoring extends \tao_actions_CommonModule
{
    protected $currentTestCenter = null;
    protected $currentDelivery   = null;

    /**
     * Temporary method to return a dummy delivery
     *
     * @return core_kernel_classes_Resource
     */
    private function _getDummyDelivery(){
        
        $fakeUri = 'my_local_ns#i9999999999999999';
        $delivery = new core_kernel_classes_Resource($fakeUri);
        if(!$delivery->exists()){
            $objectClass = new \core_kernel_classes_Class(TAO_OBJECT_CLASS);
            $delivery = $objectClass->createInstance('Dummy Delivery', 'temporarly generated delivery', $fakeUri);
        }
        return $delivery;
    }

    /**
     * Temporary method to return a dummy test center
     * 
     * @return core_kernel_classes_Resource
     */
    private function _getDummyTestCenter(){

        $fakeUri = 'my_local_ns#i111111111111111';
        $testCenter = new core_kernel_classes_Resource($fakeUri);
        if(!$testCenter->exists()){
            $objectClass = new \core_kernel_classes_Class(TAO_OBJECT_CLASS);
            $testCenter = $objectClass->createInstance('Dummy Test Center', 'temporarly generated test center', $fakeUri);
        }
        return $testCenter;
    }

    /**
     * Get the requested test center resource
     * Use this to identify which test center is currently being selected buy the proctor
     *
     * @return core_kernel_classes_Resource
     * @throws \common_Exception
     */
    protected function getCurrentTestCenter()
    {
        if (is_null($this->currentTestCenter)) {
            if($this->hasRequestParameter('testCenter')){

                //@todo remove me
                return $this->_getDummyTestCenter();

                //get test center resource from its uri
                $testCenterUri           = $this->getRequestParameter('testCenter');
                $this->currentTestCenter = new core_kernel_classes_Resource($testCenterUri);
            }else{
                //@todo use a better exception
                throw new \common_Exception('no current test center');
            }
            
        }
        return $this->currentTestCenter;
    }

    /**
     * Get the requested delivery resource
     * Use this to identify which delivery is currently being selected buy the proctor
     * 
     * @return core_kernel_classes_Resource
     * @throws \common_Exception
     */
    protected function getCurrentDelivery()
    {
        if (is_null($this->currentDelivery)) {
            if($this->hasRequestParameter('delivery')){

                //@todo remove me
                return $this->_getDummyDelivery();

                //get test center resource from its uri
                $deliveryUri           = $this->getRequestParameter('delivery');
                $this->currentDelivery = new core_kernel_classes_Resource($deliveryUri);
            }else{
                //@todo use a better exception
                throw new \common_Exception('no current delivery');
            }
        }
        return $this->currentDelivery;
    }

    /**
     * Main method to render a view for all proctoring related controller actions
     * 
     * @param string $cssClass
     * @param array $data
     * @param array $breadcrumbs
     */
    protected function composeView($cssClass, $data = array(), $breadcrumbs = array(), $template = '')
    {
        $data['breadcrumbs'] = $breadcrumbs;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->returnJson($data);
        } else {
            $this->defaultData();
            $this->setData('userLabel', SessionManager::getSession()->getUserLabel());
            $this->setData('clientConfigUrl', $this->getClientConfigUrl());
            $this->setData('cls', $cssClass);
            $this->setData('data', $data);
            $this->setData('content-template', empty($template) ? 'pages/index.tpl' : $template);
            $this->setView('layout.tpl');
        }
    }

    /**
     * Gets a list of available Test Centers for the current proctor
     *
     * @return array
     */
    protected function getTestCenters()
    {
        $user = SessionManager::getSession()->getUser();
        //get allowed test centers based on current proctor...

        $entries = array();

        $entries[] = array(
            'id' => 'locam_ns#i1000000001',
            'url' => _url('testCenter', 'TestCenter', null, array('testCenter' => 'locam_ns#i1000000001')),
            'label' => 'Room A',
            'text' => __('Go to')
        );
        $entries[] = array(
            'id' => 'locam_ns#i1000000002',
            'url' => _url('testCenter', 'TestCenter', null, array('testCenter' => 'locam_ns#i1000000002')),
            'label' => 'Room B',
            'text' => __('Go to')
        );
        $entries[] = array(
            'id' => 'locam_ns#i1000000003',
            'url' => _url('testCenter', 'TestCenter', null, array('testCenter' => 'locam_ns#i1000000003')),
            'label' => 'Room C',
            'text' => __('Go to')
        );

        return $entries;
    }

    /**
     * Gets the list of available deliveries for the selected test center
     *
     * @return array
     */
    protected function getDeliveries()
    {

        $testCenter = $this->getCurrentTestCenter();

        $entries = array();

        $all = array(
            'id' => 'all',
            'url' => _url('monitoringAll', 'Delivery', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('All Deliveries'),
            'cls' => 'dark',
            'stats' => array(
                'awaitingApproval' => 0,
                'inProgress' => 0,
                'paused' => 0
            )
        );
        
        $entries[] = array(
            'id' => 'locam_ns#i2000000001',
            'url' => _url('monitoring', 'Delivery', null, array('delivery' => 'locam_ns#i2000000001', 'testCenter' => $testCenter->getUri())),
            'label' => 'Test A',
            'stats' => array(
                'awaitingApproval' => 3,
                'inProgress' => 32,
                'paused' => 12
            ),
            'properties' => array(
                'periodStart' => '2015-11-09 00:00',
                'periodEnd' => '2015-11-17 09:20'
            )
        );
        $entries[] = array(
            'id' => 'locam_ns#i2000000002',
            'url' => _url('monitoring', 'Delivery', null, array('delivery' => 'locam_ns#i2000000002', 'testCenter' => $testCenter->getUri())),
            'label' => 'Test B',
            'stats' => array(
                'awaitingApproval' => 0,
                'inProgress' => 15,
                'paused' => 1
            ),
            'properties' => array(
                'periodStart' => '2015-11-09 00:00',
                'periodEnd' => '2015-11-17 09:20'
            )
        );
        $entries[] = array(
            'id' => 'locam_ns#i2000000003',
            'url' => _url('monitoring', 'Delivery', null, array('delivery' => 'locam_ns#i2000000003', 'testCenter' => $testCenter->getUri())),
            'label' => 'Test C',
            'stats' => array(
                'awaitingApproval' => 1,
                'inProgress' => 10,
                'paused' => 8
            ),
            'properties' => array(
                'periodStart' => '2015-11-09 00:00',
                'periodEnd' => '2015-11-17 09:20'
            )
        );
        
        $all = array_reduce($entries, function($carry, $element){
            $carry['stats']['awaitingApproval'] += $element['stats']['awaitingApproval'];
            $carry['stats']['inProgress'] += $element['stats']['inProgress'];
            $carry['stats']['paused'] += $element['stats']['paused'];
            return $carry;
        }, $all);
        
        //prepend the all delivery element to the begining of the array
        array_unshift($entries, $all);
        return $entries;
    }

    /**
     * Paginates a list of items to render a data subset in a table
     * @param array $data
     * @param array $options
     * @return array
     */
    protected function paginate($data, $options) {
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
     * Gets the data table request options
     *
     * @return array
     */
    protected function getRequestOptions() {

        $page = $this->hasRequestParameter('page') ? $this->getRequestParameter('page') : 1;
        $rows = $this->hasRequestParameter('rows') ? $this->getRequestParameter('rows') : 15;
        $sortBy = $this->hasRequestParameter('sortby') ? $this->getRequestParameter('sortby') : 'firstname';
        $sortOrder = $this->hasRequestParameter('sortorder') ? $this->getRequestParameter('sortorder') : 'asc';
        $filter = $this->hasRequestParameter('filter') ? $this->getRequestParameter('filter') : null;
        $periodStart = $this->hasRequestParameter('periodStart') ? $this->getRequestParameter('periodStart') : null;
        $periodEnd = $this->hasRequestParameter('periodEnd') ? $this->getRequestParameter('periodEnd') : null;

        return array(
            'page' => $page,
            'rows' => $rows,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filter' => $filter,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd
        );

    }

    /**
     * Gets the value of a string property from a user
     * @param User $user
     * @param string $property
     * @return mixed|string
     */
    protected function getUserStringProp($user, $property) {
        $value = $user->getPropertyValues($property);
        return empty($value) ? '' : current($value);
    }
}
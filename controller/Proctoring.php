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
use oat\taoProctoring\helpers\Delivery;
use oat\taoProctoring\helpers\TestCenter;

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

                //get test center resource from its uri
                $testCenterUri           = $this->getRequestParameter('testCenter');
                $this->currentTestCenter = TestCenter::getTestCenter($testCenterUri);
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

                //get test center resource from its uri
                $deliveryUri           = $this->getRequestParameter('delivery');
                $this->currentDelivery = Delivery::getDelivery($deliveryUri);
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

        return array(
            'page' => $page,
            'rows' => $rows,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filter' => $filter,
        );

    }
}
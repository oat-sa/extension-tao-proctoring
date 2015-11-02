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
                'url' => _url('diagnostic', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Readiness Check'),
                'content' => __('Check the compatibility of the current workstation and see the results'),
                'text' => __('Go'),
                'width' => 4
            ),
            array(
                'url' => _url('index', 'ProctorDelivery', null, array('id' => $id)),
                'label' => __('Deliveries'),
                'content' => __('Monitor and manage the deliveries of the test site'),
                'text' => __('Go'),
                'width' => 4
            ),
            array(
                'url' => _url('report', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Assessment Activity Reporting'),
                'content' => __('Generate and review test histories'),
                'text' => __('Go'),
                'width' => 4
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
            $this->setPage('diagnostic', array(
                'id' => $id,
                'title' => __('Readiness Check for test site %s', $id),
                'list' => array(
                    array(
                        'url' => _url('diagnostic', 'TaoProctoring', null, array('id' => $id)),
                        'label' => __('This page is under construction. Please go back later...'),
                        'text' => __('Refresh'),  
                    ),
                ),
                'breadcrumbs' => $this->getBreadcrumbs($id, array(
                    'id' => 'diagnostic',
                    'label' => __('Readiness Check'),
                    'entries' => array(
                        array(
                            'id' => 'deliveries',
                            'url' => _url('index', 'ProctorDelivery', null, array('id' => $id)),
                            'label' => __('Deliveries'),
                        ),
                        array(
                            'id' => 'report',
                            'url' => _url('report', 'TaoProctoring', null, array('id' => $id)),
                            'label' => __('Assessment Activity Reporting'),
                        ),
                    ),
                )),
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
            $this->setPage('report', array(
                'id' => $id,
                'title' => __('Assessment Activity Reporting for test site %s', $id),
                'list' => array(
                    array(
                        'url' => _url('report', 'TaoProctoring', null, array('id' => $id)),
                        'label' => __('This page is under construction. Please go back later...'),
                        'text' => __('Refresh'),  
                    ),
                ),
                'breadcrumbs' => $this->getBreadcrumbs($id, array(
                    'id' => 'report',
                    'label' => __('Assessment Activity Reporting'),
                    'entries' => array(
                        array(
                            'id' => 'diagnostic',
                            'url' => _url('diagnostic', 'TaoProctoring', null, array('id' => $id)),
                            'label' => __('Readiness Check'),
                        ),
                        array(
                            'id' => 'deliveries',
                            'url' => _url('index', 'ProctorDelivery', null, array('id' => $id)),
                            'label' => __('Deliveries'),
                        ),
                    ),
                )),
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

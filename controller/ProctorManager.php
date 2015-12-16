<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */
namespace oat\taoProctoring\controller;

use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;

/**
 * Proctor manager controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class ProctorManager extends ProctoringModule
{
    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $data = array(
            'list' => $testCenters,
            'administrator' => true //check if the current user is a test site administrator or not
        );

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'proctorManager-index',
                $data,
                array(
                    BreadcrumbsHelper::testCenters(),
                    BreadcrumbsHelper::proctorManager()
                )
            );
        }
    }

    protected function getRequestTestCenters(){
        if($this->hasRequestParameter('testCenters')){
            return $this->hasRequestParameter('testCenters');
        }else{
            throw new \common_Exception('no testCenters in request param');
        }
    }

    protected function getRequestProctors(){
        if($this->hasRequestParameter('proctors')){
            return $this->hasRequestParameter('proctors');
        }else{
            throw new \common_Exception('no proctors in request param');
        }
    }

    protected function getAuthorization($testCenters){
        
        //call service getProctorsAuthorization($testCenters);
        $authorizations = array();

        //call service getAssignedProctors($currentUser?)
        $proctors = array();

        //merge the data from $authorizations and $proctors arrays and format it for the datatable
        

        //return value
        return $authorizations;
    }
    
    /**
     * Get the lists of authorization for the proctor of the selected test centers
     */
    public function proctorAuthorizations()
    {
        $testCenters = $this->getRequestTestCenters();

        //return data
        return $this->returnJson(array(
            'authorization' => $this->getAuthorization($testCenters)
        ));
    }

    /**
     * Authorize the proctors to test centers
     */
    public function authorize(){

        $proctors = $this->getRequestProctors();
        $testCenters = $this->getRequestTestCenters();

        //call service authorizeProctors($proctors, $testCenters);
        $success = true;

        //return data
        return $this->returnJson(array(
            'success' => $success
        ));
    }

    /**
     * Unauthorize the proctors from test centers
     */
    public function unauthorize(){

        $proctors = $this->getRequestProctors();
        $testCenters = $this->getRequestTestCenters();

        //call service unauthorizeProctors($proctors, $testCenters);
        $success = true;

        //return data
        return $this->returnJson(array(
            'success' => $success
        ));
    }

    /**
     * Returns the proctor creation form
     */
    public function createProctorForm(){

        $form = '<form>';
        
        //return data
        return $this->returnJson(array(
            'form' => $form
        ));
    }

    /**
     * Create a proctor
     */
    public function createProctor(){

        //call service
        $success = true;

        //return data
        return $this->returnJson(array(
            'success' => $success
        ));
    }
}

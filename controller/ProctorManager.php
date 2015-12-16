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

use \tao_helpers_Uri;
use \tao_helpers_Request;
use common_session_SessionManager as SessionManager;
use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;
use oat\taoProctoring\controller\form\AddProctor;
use oat\taoProctoring\model\ProctorManagementService;

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

        if (tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'proctorManager-index',
                $data,
                array(
                    BreadcrumbsHelper::testCenters(),
                    BreadcrumbsHelper::proctorManager()
                ),
                'pages/proctorManager.tpl'
            );
        }
    }

    protected function getRequestTestCenters(){
        if($this->hasRequestParameter('testCenters')){
            return $this->hasRequestParameter('testCenters');
        }else{
            return array();//may be empty
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

        $myFormContainer = new AddProctor();
        $myForm = $myFormContainer->getForm();
        $valid = false;
        $created = false;
        $form = '';
        
        if ($myForm->isSubmited()) {
            $valid = $myForm->isValid();
            if ($valid) {
                $values = $myForm->getValues();
                $values[PROPERTY_USER_PASSWORD] = \core_kernel_users_Service::getPasswordHash()->encrypt($values['password1']);
                unset($values['password1']);
                unset($values['password2']);

                //force the new user role to be proctorRole
                $values[PROPERTY_USER_ROLES] = array('http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole');//@todo use a constant instead
                $proctor = $myFormContainer->getUser();
                $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($proctor);
                $created = $binder->bind($values);
                if($created){
                    //assign then authorize the new proctor to the selected test centers
                    $testCenters = $this->getRequestTestCenters();
                    ProctorManagementService::singleton()->assignProctors(array($proctor->getUri()), SessionManager::getSession()->getUserUri());
                    ProctorManagementService::singleton()->authorizeProctors(array($proctor->getUri()), $testCenters);
                }
            }else{
                $form = $myForm->render();
            }
        }else{
            $form = $myForm->render();
        }
        
        return $this->returnJson(array(
            'form' => $form,
            'valid' => $valid,
            'created' => $created,
            'loginId'=> tao_helpers_Uri::encode(PROPERTY_USER_LOGIN),
            'debug' => array('values' => $myForm->getValues())
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

    /**
     * action used to check if a login can be used
     * @return void
     */
    public function checkLogin()
    {
        if (!tao_helpers_Request::isAjax()) {
            throw new Exception("wrong request mode");
        }

        $available = false;
        if ($this->hasRequestParameter('login')) {
            $available = \tao_models_classes_UserService::singleton()->loginAvailable($this->getRequestParameter('login'));
        }

        $this->returnJson(array('available' => $available));
    }
}

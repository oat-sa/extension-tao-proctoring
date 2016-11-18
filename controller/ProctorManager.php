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

use oat\tao\helpers\UserHelper;
use oat\taoProctoring\helpers\DataTableHelper;
use \tao_helpers_Uri;
use \tao_helpers_Request;
use common_session_SessionManager as SessionManager;
use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;
use oat\taoProctoring\controller\form\AddProctor;
use oat\taoProctoring\model\ProctorManagementService;
use oat\taoProctoring\model\textConverter\ProctoringTextConverterTrait;

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
    use ProctoringTextConverterTrait;

    /**
     * The proctor is not authorized on the selected test centers
     */
    const NOT_AUTHORIZED = 0;

    /**
     * The proctor is only authorized on a subset of the selected test centers
     */
    const PARTIALLY_AUTHORIZED = 1;

    /**
     * The proctor is authorized on all the selected test centers
     */
    const FULLY_AUTHORIZED = 2;

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $data = array(
            'list' => $testCenters
        );

        if (tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->setData('select-message', $this->convert('Please select one or more test site to manage proctors'));
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

    /**
     * Gets the list of test centers from the request
     * @return bool
     * @throws \common_Exception
     */
    protected function getRequestTestCenters(){
        if($this->hasRequestParameter('testCenters')){
            return $this->getRequestParameter('testCenters');
        }else{
            return array();//may be empty
        }
    }

    /**
     * Gets the list of proctors from the request
     * @return bool
     * @throws \common_Exception
     */
    protected function getRequestProctors(){
        if($this->hasRequestParameter('proctors')){
            return $this->getRequestParameter('proctors');
        }else{
            throw new \common_Exception($this->convert('No proctors in request param'));
        }
    }

    /**
     * Gets the list of authorized proctors for a selection of test centers
     * @param $testCenters
     * @return array
     * @throws \common_exception_Error
     */
    protected function getAuthorization($testCenters)
    {
        $requestOptions = $this->getRequestOptions();
        $currentUser = SessionManager::getSession()->getUser();
        $proctors = ProctorManagementService::singleton()->getAssignedProctors($currentUser->getIdentifier(), $testCenters);

        return DataTableHelper::paginate($proctors, $requestOptions, function($proctors) use($testCenters) {
            $testCentersByProctors = ProctorManagementService::singleton()->getProctorsAuthorization($testCenters);
            $nbTestCenters = count($testCenters);

            $authorizations = array();

            foreach($proctors as $proctor) {
                $userId = $proctor->getUri();
                $user = UserHelper::getUser($proctor);
                $lastName = UserHelper::getUserLastName($user);
                $firstName = UserHelper::getUserFirstName($user, empty($lastName));
                $login = UserHelper::getUserStringProp($user, PROPERTY_USER_LOGIN);
                $authorizedLabel = array();

                if (isset($testCentersByProctors[$userId])) {
                    $authorized = array_intersect($testCentersByProctors[$userId], $testCenters);
                    if (count($authorized) == $nbTestCenters) {
                        $status = self::FULLY_AUTHORIZED;
                    } else {
                        foreach($authorized as $testCenterUri){
                            $testCenter = new \core_kernel_classes_Resource($testCenterUri);
                            $authorizedLabel[] = $testCenter->getLabel();
                        }
                        $status = self::PARTIALLY_AUTHORIZED;
                    }
                } else {
                    $status = self::NOT_AUTHORIZED;
                }

                $authorizations[] = array(
                    'id' => $userId,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'login' => $login,
                    'status' => $status,
                    'authorized' => implode(', ', $authorizedLabel)
                );
            }

            return $authorizations;
        });
    }

    /**
     * Get the lists of authorization for the proctor of the selected test centers
     */
    public function proctorAuthorizations()
    {
        $testCenters = $this->getRequestTestCenters();

        //return data
        $this->returnJson($this->getAuthorization($testCenters));
    }

    /**
     * Authorize the proctors to test centers
     */
    public function authorize(){

        $proctors = $this->getRequestProctors();
        $testCenters = $this->getRequestTestCenters();

        $success = ProctorManagementService::singleton()->authorizeProctors($proctors, $testCenters);

        //return data
        $this->returnJson(array(
            'success' => $success
        ));
    }

    /**
     * Unauthorize the proctors from test centers
     */
    public function unauthorize(){

        $proctors = $this->getRequestProctors();
        $testCenters = $this->getRequestTestCenters();

        $success = ProctorManagementService::singleton()->unauthorizeProctors($proctors, $testCenters);

        //return data
        $this->returnJson(array(
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
                    ProctorManagementService::singleton()->assignProctors(array($proctor->getUri()), SessionManager::getSession()->getUserUri());
                    $testCenters = $this->getRequestTestCenters();
                    if(!empty($testCenters)){
                        ProctorManagementService::singleton()->authorizeProctors(array($proctor->getUri()), $testCenters);
                    }
                }
            }else{
                $form = $myForm->render();
            }
        }else{
            $form = $myForm->render();
        }
        
        $this->returnJson(array(
            'form' => $form,
            'valid' => $valid,
            'created' => $created,
            'loginId'=> tao_helpers_Uri::encode(PROPERTY_USER_LOGIN),
            'debug' => array('values' => $myForm->getValues())
        ));
    }

    /**
     * action used to check if a login can be used
     * @throws \Exception
     */
    public function checkLogin()
    {
        if (!tao_helpers_Request::isAjax()) {
            throw new \Exception("wrong request mode");
        }

        $available = false;
        if ($this->hasRequestParameter('login')) {
            $available = \tao_models_classes_UserService::singleton()->loginAvailable($this->getRequestParameter('login'));
        }

        $this->returnJson(array('available' => $available));
    }
}

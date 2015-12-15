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

use oat\taoProctoring\model\TestCenterService;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\helpers\DataTableHelper;

/**
 * Proctoring Test Center controllers for test center screens
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class TestCenterManager extends \tao_actions_SaSModule
{
    /**
     * Initialize the service and the default data
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
        $this->eligibilityService = EligibilityService::singleton();
    }

    protected function getClassService()
    {
        return TestCenterService::singleton();
    }

    /**
     * Edit a Test Center instance
     * @return void
     */
    public function editCenter()
    {
        $clazz = $this->getCurrentClass();
        $testCenter = $this->getCurrentInstance();

        $formContainer = new \tao_actions_form_Instance($clazz, $testCenter);
        $myForm = $formContainer->getForm();
        if ($myForm->isSubmited()) {
            if ($myForm->isValid()) {

                $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($testCenter);
                $testCenter = $binder->bind($myForm->getValues());

                $this->setData("selectNode", \tao_helpers_Uri::encode($testCenter->getUri()));
                $this->setData('message', __('Test center saved'));
                $this->setData('reload', true);
            }
        }

        $childrenProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_CHILDREN_URI);
        $childrenForm = \tao_helpers_form_GenerisTreeForm::buildTree($testCenter, $childrenProperty);
        $childrenForm->setHiddenNodes(array($testCenter->getUri()));
        $childrenForm->setTitle(__('Define sub-centers'));
        $this->setData('childrenForm', $childrenForm->render());

        $administratorProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_ADMINISTRATOR_URI);
        $administratorForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $administratorProperty);
        $administratorForm->setData('title', __('Assign administrator'));
        $this->setData('administratorForm', $administratorForm->render());

        $proctorProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_PROCTORS_URI);
        $proctorForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $proctorProperty);
        $proctorForm->setData('title', __('Assign proctors'));
        $this->setData('proctorForm', $proctorForm->render());

        $deliveryClass = new \core_kernel_classes_Class(TAO_DELIVERY_CLASS);
        $deliveries = $deliveryClass->getInstances(true);
        $deliveriesFormated = array_map(function($delivery){
            return array(
                'uri' => $delivery->getUri(),
                'label' => $delivery->getLabel()
            );
        }, array_values($deliveries));

        $this->setData('eligibilities', json_encode($this->_getEligibilities()));
        $this->setData('deliveries', json_encode($deliveriesFormated));
        $this->setData('formTitle', __('Edit test center'));
        $this->setData('testCenter', $testCenter->getUri());
        $this->setData('myForm', $myForm->render());
        $this->setView('form_test_center.tpl');
    }

    /**
     * get eligible deliveries data formated in a way that is compatible with the client ui/datatable component
     *
     * @return {array}
     */
    private function _getEligibilities(){

        $testCenter = $this->getCurrentInstance();
        $eligibilityService = $this->eligibilityService;
        $eligibilities = $eligibilityService->getEligibleDeliveries($testCenter);

        $data = array_map(function($delivery) use ($eligibilityService, $testCenter){
            return array(
                'id' => $delivery->getUri(),
                'testTakers' => array_map(function($testTakerId){
                    $ttaker = new \core_kernel_classes_Resource($testTakerId);
                    return array(
                        'label' => $ttaker->getLabel(),
                        'uri' => $testTakerId,
                        'encodedUri' => \tao_helpers_Uri::encode($testTakerId)//jstree use id formated this way...
                    );
                }, $eligibilityService->getEligibleTestTakers($testCenter, $delivery))
            );
        }, $eligibilities);

        return DataTableHelper::paginate($data, $this->getRequestOptions());
    }

    /**
     * Get the requested eligibility to be edited
     * 
     * @return type
     * @throws \common_Exception
     */
    private function _getRequestEligibility(){
        if($this->hasRequestParameter('eligibility')){
            $eligibility = $this->getRequestParameter('eligibility');
            if(isset($eligibility['deliveries']) && is_array($eligibility['deliveries'])){

                $formatted = array();
                $formatted['deliveries'] = array_map(function($deliveryUri){
                        return new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($deliveryUri));
                    }, $eligibility['deliveries']);

                if(isset($eligibility['testTakers']) && is_array($eligibility['testTakers'])){
                    $formatted['testTakers'] = array_map(function($testTakerId){
                        return \tao_helpers_Uri::decode($testTakerId);
                    }, $eligibility['testTakers']);
                }

                return $formatted;
            }else{
                throw new \common_Exception('eligibility requires a delivery');
            }
        }else{
            throw new \common_Exception('no eligibility in request');
        }
    }

    public function getEligibilities()
    {
        return $this->returnJson($this->_getEligibilities());
    }

    public function addEligibilities()
    {
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->_getRequestEligibility();
        foreach($eligibility['deliveries'] as $delivery){
            $success = $this->eligibilityService->createEligibility($testCenter, $delivery);
            if($success && isset($eligibility['testTakers'])){
                $success = $this->eligibilityService->setEligibleTestTakers($testCenter, $delivery, $eligibility['testTakers']);
            }
        }

        return $this->returnJson(array(
            'success' => $success,
            'eligibility' => $eligibility
        ));
    }

    public function editEligibilities()
    {
        $success = false;
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->_getRequestEligibility();
        if(isset($eligibility['testTakers'])){
            foreach($eligibility['deliveries'] as $delivery){
                $success = $this->eligibilityService->setEligibleTestTakers($testCenter, $delivery, $eligibility['testTakers']);
            }
        }
        return $this->returnJson(array(
            'success' => $success
        ));
    }

    public function removeEligibilities()
    {
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->_getRequestEligibility();
        foreach($eligibility['deliveries'] as $delivery){
            $success = $this->eligibilityService->removeEligibility($testCenter, $delivery);
        }
        return $this->returnJson(array(
            'success' => $success
        ));
    }

    /**
     * Gets the data table request options
     *
     * @return array
     */
    protected function getRequestOptions() {

        $page = $this->hasRequestParameter('page') ? $this->getRequestParameter('page') : DataTableHelper::DEFAULT_PAGE;
        $rows = $this->hasRequestParameter('rows') ? $this->getRequestParameter('rows') : DataTableHelper::DEFAULT_ROWS;
        $sortBy = $this->hasRequestParameter('sortby') ? $this->getRequestParameter('sortby') : 'Delivery';
        $sortOrder = $this->hasRequestParameter('sortorder') ? $this->getRequestParameter('sortorder') : 'asc';
        $filter = $this->hasRequestParameter('filter') ? $this->getRequestParameter('filter') : null;

        return array(
            'page' => $page,
            'rows' => $rows,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filter' => $filter
        );

    }
}
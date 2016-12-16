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
use oat\taoProctoring\model\ProctorManagementService;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\helpers\DataTableHelper;
use oat\taoProctoring\model\textConverter\ProctoringTextConverterTrait;

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
    use ProctoringTextConverterTrait;

    protected $eligibilityService;

    /**
     * Initialize the service and the default data
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
        $this->eligibilityService = $this->getServiceManager()->get(EligibilityService::SERVICE_ID);
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
                $this->setData('message', $this->convert('Test center saved'));
                $this->setData('reload', true);
            }
        }

        $childrenProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_CHILDREN_URI);
        $childrenForm = \tao_helpers_form_GenerisTreeForm::buildTree($testCenter, $childrenProperty);
        $childrenForm->setHiddenNodes(array($testCenter->getUri()));
        $childrenForm->setTitle($this->convert('Define sub-centers'));
        $this->setData('childrenForm', $childrenForm->render());

        $administratorProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_ADMINISTRATOR_URI);
        $administratorForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $administratorProperty);
        $administratorForm->setData('title', $this->convert('Assign administrator'));
        $this->setData('administratorForm', $administratorForm->render());

        $proctorProperty = new \core_kernel_classes_Property(ProctorManagementService::PROPERTY_ASSIGNED_PROCTOR_URI);
        $proctorForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $proctorProperty);
        $proctorForm->setData('title', $this->convert('Assign proctors'));
        $this->setData('proctorForm', $proctorForm->render());

        $this->setData('formTitle', $this->convert('Edit test center'));
        $this->setData('testCenter', $testCenter->getUri());
        $this->setData('myForm', $myForm->render());
        $this->setView('form_test_center.tpl');
    }

    /**
     * Get eligiblities formated in a way that is compatible 
     * with the eligibilityTable component
     *
     * @return array
     */
    private function _getEligibilities(){

        $testCenter = $this->getCurrentInstance();

        $data = array_map(function($eligibility) {
            $eligibility['id'] = $eligibility['uri'];
            return $eligibility;
        }, $this->eligibilityService->getEligibilities($testCenter, [ 'sort' => true ]));

        return DataTableHelper::paginate($data, $this->getRequestOptions());
    }

    /**
     * Get the list of eligibilities.
     *
     * Reformat them for compat.
     *
     * @return array
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
        $failures = array();
        $success = true;
        foreach($eligibility['deliveries'] as $delivery){
            if($delivery->isClass()){
                continue;//prevent assigning eligibility to a class for now
            }
            if($this->eligibilityService->createEligibility($testCenter, $delivery)){
                if(isset($eligibility['testTakers'])){
                    $success &= $this->eligibilityService->setEligibleTestTakers($testCenter, $delivery, $eligibility['testTakers']);
                }
            }else{
                $success = false;
                $failures[] = $delivery->getLabel();
            }
        }

        return $this->returnJson(array(
            'success' => $success,
            'failed' => $failures
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
        }else{
            //nothing to save, so consider it done
            $success = true;
        }
        return $this->returnJson(array(
            'success' => $success
        ));
    }

    /**
     * Remove the eligibility in parameter
     * @throws \common_Exception without an eligibility
     */
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
     * Change the eligibility in parameter to use the proctored authorization (shield)
     * @throws \common_Exception without an eligibility
     */
    public function shieldEligibility()
    {
        if(!$this->hasRequestParameter('eligibility')){
            throw new \common_Exception('Please provide the URI of the eligibilty to shield');
        }
        $eligibilityUri = $this->getRequestParameter('eligibility');

        $this->eligibilityService->setByPassProctor(new \core_kernel_classes_Resource($eligibilityUri), false);
        return $this->returnJson(array(
            'success' => true
        ));
    }

    /**
     * Change the eligibility in parameter to use the default authorization (unshield)
     * @throws \common_Exception without an eligibility
     */
    public function unshieldEligibility()
    {
        if(!$this->hasRequestParameter('eligibility')){
            throw new \common_Exception('Please provide the URI of the eligibilty to unshield');
        }
        $eligibilityUri = $this->getRequestParameter('eligibility');

        $this->eligibilityService->setByPassProctor(new \core_kernel_classes_Resource($eligibilityUri), true);
        return $this->returnJson(array(
            'success' => true
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

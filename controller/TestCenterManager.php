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

        $memberProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_MEMBERS_URI);
        $memberForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $memberProperty);
        $memberForm->setData('title', __('Assign test-takers'));
        $this->setData('memberForm', $memberForm->render());

        $deliveryProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_DELIVERY_URI);
        $deliveryForm = \tao_helpers_form_GenerisTreeForm::buildTree($testCenter, $deliveryProperty);
        $deliveryForm->setData('title', __('Assign available deliveries'));
        $this->setData('deliveryForm', $deliveryForm->render());

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

    private function _getEligibilities(){
        $testCenter = $this->getCurrentInstance();
        $eligibilityService = $this->eligibilityService;
        $eligibilities = $eligibilityService->getEligibleDeliveries($testCenter);
        return array_map(function($delivery) use ($eligibilityService, $testCenter){
            return array(
                'delivery' => $delivery->getUri(),
                'testTakers' => $eligibilityService->getEligibleTestTakers($testCenter, $delivery)
            );
        }, $eligibilities);
    }

    private function getRequestEligibility(){
        if($this->hasRequestParameter('eligibility')){
            $eligibility = $this->getRequestParameter('eligibility');
            if(isset($eligibility['delivery']) && !empty($eligibility['delivery'])){
                $formatted = array(
                    'delivery' => new \core_kernel_classes_Resource($eligibility['delivery'])
                );
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
        return $this->returnJson(array(
            'delivieries' => $this->_getEligibilities()
        ));
    }   

    public function addEligibility()
    {
        $success = false;
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->getRequestEligibility();
        if($this->eligibilityService->createEligibility($testCenter, $eligibility['delivery'])){
            $success = $this->eligibilityService->setEligibleTestTakers($testCenter, $eligibility['delivery'], $eligibility['testTakers']);
        }
        return $this->returnJson(array(
            'sucess' => $success
        ));
    }

    public function editEligibility()
    {
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->getRequestEligibility();
        $success = $this->eligibilityService->setEligibleTestTakers($testCenter, $eligibility['delivery'], $eligibility['testTakers']);
        return $this->returnJson(array(
            'sucess' => $success
        ));
    }

    public function removeEligibility()
    {
        $testCenter = $this->getCurrentInstance();
        $eligibility = $this->getRequestEligibility();
        $success = $this->eligibilityService->removeEligibility($testCenter, $eligibility['delivery']);
        return $this->returnJson(array(
            'sucess' => $success
        ));
    }

}
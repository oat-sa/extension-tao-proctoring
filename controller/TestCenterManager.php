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

        $eligibilityService = EligibilityService::singleton();
        $eligibilities = $eligibilityService->getEligibleDeliveries($testCenter);
        $eligibilitiesFormated = array_map(function($delivery) use ($eligibilityService, $testCenter){
            return array(
                'delivery' => $delivery->getUri(),
                'testTakers' => $eligibilityService->getEligibleTestTakers($testCenter, $delivery)
            );
        }, $eligibilities);

        $deliveryClass = new \core_kernel_classes_Class(TAO_DELIVERY_CLASS);
        $deliveries = $deliveryClass->getInstances(true);
        $deliveriesFormated = array_map(function($delivery){
            return array(
                'uri' => $delivery->getUri(),
                'label' => $delivery->getLabel()
            );
        }, array_values($deliveries));

        

        $this->setData('eligibilities', json_encode($eligibilitiesFormated));
        $this->setData('deliveries', json_encode($deliveriesFormated));
        $this->setData('formTitle', __('Edit test center'));
        $this->setData('testCenter', $testCenter->getUri());
        $this->setData('myForm', $myForm->render());
        $this->setView('form_test_center.tpl');
    }
    
    public function allowDelivery()
    {
        $delivery = new \core_kernel_classes_Resource('https://tao31.bout/my_tao31.rdf#i1449654679931281');
        $testCenter = new \core_kernel_classes_Resource($this->getRequestParameter('testCenter'));
        $success = EligibilityService::singleton()->createEligibility($testCenter, $delivery);
        $this->returnJson(array('success' => $success));
    }
    
    public function getEligibilities()
    {
        $testCenter = new \core_kernel_classes_Resource($this->getRequestParameter('testCenter'));
        $deliveries = EligibilityService::singleton()->getEligibleDeliveries($testCenter);
        
        $deliveryData = array();
        foreach ($deliveries as $delivery) {
            $deliveryData[$delivery->getUri()] = $delivery->getLabel(); 
        }
        return $this->returnJson(array(
            'delivieries' => $deliveryData
        ));
    }   
}
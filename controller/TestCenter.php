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

use oat\taoProctoring\helpers\Breadcrumbs;
use oat\taoProctoring\helpers\TestCenter as TestCenterHelper;
use oat\taoProctoring\model\TestCenterService;

/**
 * Proctoring Test Center controllers for test center screens
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class TestCenter extends \tao_actions_SaSModule implements ProctoringInterface
{
    use ProctoringTrait;

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

        $memberProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_MEMBERS_URI);
        $memberForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $memberProperty);
        $memberForm->setData('title', __('Select test-takers for the test center'));
        $this->setData('memberForm', $memberForm->render());

        $groupProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_DELIVERY_URI);
        $groupForm = \tao_helpers_form_GenerisTreeForm::buildTree($testCenter, $groupProperty);
        $groupForm->setData('title', __('Select deliveries available at the test center'));
        $this->setData('groupForm', $groupForm->render());

        $proctorProperty = new \core_kernel_classes_Property(TestCenterService::PROPERTY_PROCTORS_URI);
        $proctorForm = \tao_helpers_form_GenerisTreeForm::buildReverseTree($testCenter, $proctorProperty);
        $proctorForm->setData('title', __('Select proctors for the test center'));
        $this->setData('proctorForm', $proctorForm->render());

        $this->setData('formTitle', __('Edit test center'));
        $this->setData('myForm', $myForm->render());
        $this->setView('form_test_center.tpl');
    }

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $data = array(
            'list' => $testCenters
        );

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-index',
                $data,
                array(
                    Breadcrumbs::testCenters()
                )
            );
        }
    }

    /**
     * Displays the three action box for the test center
     */
    public function testCenter()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $testCenter  = $this->getCurrentTestCenter();
        $data = array(
            'testCenter' => $testCenter->getUri(),
            'title' => __('Test site %s', $testCenter->getLabel()),
            'list' => TestCenterHelper::getTestCenterActions($testCenter)
        );

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-testcenter',
                $data,
                array(
                    Breadcrumbs::testCenters(),
                    Breadcrumbs::testCenter($testCenter, $testCenters)
                )
            );
        }
    }
}
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

use common_session_SessionManager as SessionManager;
use core_kernel_classes_Resource;
use oat\taoProctoring\helpers\DataTableHelper;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\helpers\TestCenterHelper;
use oat\taoProctoring\helpers\ReasonCategoryHelper;
use DateTime;
use oat\taoProctoring\model\ReasonCategoryService;

/**
 * Base proctoring interface controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
abstract class ProctoringModule extends \tao_actions_CommonModule
{
    const DEFAULT_SORT_COLUMN = 'firstname';
    const DEFAULT_SORT_ORDER = 'asc';
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
                $this->currentTestCenter = TestCenterHelper::getTestCenter($testCenterUri);
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
     * @param bool $mandatory Throws an exception if the delivery is not provided
     * @return core_kernel_classes_Resource
     * @throws \common_Exception
     */
    protected function getCurrentDelivery($mandatory = true)
    {
        if (is_null($this->currentDelivery)) {
            if ($this->hasRequestParameter('delivery')) {

                //get test center resource from its uri
                $deliveryUri           = $this->getRequestParameter('delivery');
                $this->currentDelivery = DeliveryHelper::getDelivery($deliveryUri);
            } else if ($mandatory) {
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
     * @param String $template
     */
    protected function composeView($cssClass, $data = array(), $breadcrumbs = array(), $template = '')
    {
        $data['breadcrumbs'] = $breadcrumbs;

        foreach($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $data[$key] = json_encode($value);
            }
        }

        $this->defaultData();
        $this->setData('userLabel', SessionManager::getSession()->getUserLabel());
        $this->setData('clientConfigUrl', $this->getClientConfigUrl());
        $this->setData('cls', $cssClass);
        $this->setData('data', $data);
        $this->setData('content-template', empty($template) ? 'pages/index.tpl' : $template);
        $this->setView('layout.tpl');
    }

    /**
     * Gets the data table request options
     *
     * @param array $defaults
     * @return array
     */
    protected function getRequestOptions(array $defaults = []) {

        $defaults = array_merge($this->getDefaultOptions(), $defaults);

        $page = $this->hasRequestParameter('page') ? $this->getRequestParameter('page') : $defaults['page'];
        $rows = $this->hasRequestParameter('rows') ? $this->getRequestParameter('rows') : $defaults['rows'];
        $sortBy = $this->hasRequestParameter('sortby') ? $this->getRequestParameter('sortby') : $defaults['sortby'];
        $sortOrder = $this->hasRequestParameter('sortorder') ? $this->getRequestParameter('sortorder') : $defaults['sortorder'];
        $filter = $this->hasRequestParameter('filter') ? $this->getRequestParameter('filter') : $defaults['filter'];
        $filterquery = $this->hasRequestParameter('filterquery') ? $this->getRequestParameter('filterquery') : $defaults['filter'];
        $periodStart = $this->hasRequestParameter('periodStart') ? $this->getRequestParameter('periodStart') : $defaults['periodStart'];
        $periodEnd = $this->hasRequestParameter('periodEnd') ? $this->getRequestParameter('periodEnd') : $defaults['periodEnd'];
        $detailed = $this->hasRequestParameter('detailed') ? $this->getRequestParameter('detailed') : 'false';
        $detailed = filter_var($detailed, FILTER_VALIDATE_BOOLEAN);

        return array(
            'page' => $page,
            'rows' => $rows,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filter' => $filter ? $filter : $filterquery,
            'periodStart' => $periodStart,
            'detailed' => $detailed,
            'periodEnd' => $periodEnd
        );

    }

    /**
     * @return array
     */
    private function getDefaultOptions()
    {
        $today = new DateTime();
        return [
            'page' => DataTableHelper::DEFAULT_PAGE,
            'rows' => DataTableHelper::DEFAULT_ROWS,
            'sortby' => self::DEFAULT_SORT_COLUMN,
            'sortorder' => self::DEFAULT_SORT_ORDER,
            'filter' => null,
            'periodStart' => $today->format('Y-m-d'),
            'periodEnd' => $today->format('Y-m-d')
        ];
    }

    /**
     * Get the list of all available categories, sorted by action names
     *
     * @return array
     */
    protected function getAllReasonsCategories(){
        /** @var ReasonCategoryService $categoryService */
        $categoryService = $this->getServiceManager()->get(ReasonCategoryService::SERVICE_ID);

        return array(
            'authorize' => array(),
            'pause' => $categoryService->getIrregularities(),
            'terminate' => $categoryService->getIrregularities(),
            'report' => $categoryService->getIrregularities(),
            'print' => [],
        );
    }

    /**
     * Check is the current user in session is a proctor admin or not
     * @return boolean
     */
    protected function isAdmin(){
        $testSiteAdminRoleUri = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterAdministratorRole';
        $roles = SessionManager::getSession()->getUserRoles();
        return (isset($roles[$testSiteAdminRoleUri]) && $roles[$testSiteAdminRoleUri] = $testSiteAdminRoleUri);
    }
}
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
use oat\tao\model\mvc\DefaultUrlService;
use oat\taoProctoring\helpers\DataTableHelper;
use DateTime;

/**
 * Base proctoring interface controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
abstract class SimplePageModule extends \tao_actions_SinglePageModule
{
    /**
     * Retrieve the data from the url and make the base initialization
     *
     * @return void
     */
    protected function defaultData()
    {
        parent::defaultData();
        $this->setData('userLabel', SessionManager::getSession()->getUserLabel());
    }

    /**
     * Gets the path to the layout
     * @return array
     */
    protected function getLayout()
    {
        $this->setData('homeUrl', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringHome'));
        $this->setData('logout', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringLogout'));
        return ['layout.tpl', 'taoProctoring'];
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
            'sortby' => 'id',
            'sortorder' => DataTableHelper::DEFAULT_SORT_ORDER,
            'filter' => null,
            'periodStart' => $today->format('Y-m-d'),
            'periodEnd' => $today->format('Y-m-d')
        ];
    }

}

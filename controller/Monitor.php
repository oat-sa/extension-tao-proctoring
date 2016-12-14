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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;

use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\delivery\DeliveryService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\ReasonCategoryService;

/**
 * Monitoring Delivery controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Monitor extends SimplePageModule
{
    use OntologyAwareTrait;
    
    /**
     * Displays the index page of the deliveries list all available deliveries for the current test center
     */
    public function index()
    {
        $service = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $delivery = $this->getResource($this->getRequestParameter('delivery'));
        $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery);
        $this->composeView(
            'delivery-monitoring',
            array(
                'ismanageable' => false,
                'delivery' => $delivery->getUri(),
                'set' => DeliveryHelper::buildDeliveryExecutionData($executions),
                'extrafields' => DeliveryHelper::getExtraFields(),
                'categories' => $this->getAllReasonsCategories(),
                'printReportButton' => json_encode(false),
                'timeHandling' => json_encode(false),
            ),
            array(
            ),
            'Monitoring/index.tpl'
        );
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
}

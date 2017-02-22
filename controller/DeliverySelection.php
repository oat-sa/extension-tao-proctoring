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

use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\ProctorService;

/**
 * Proctoring Delivery controllers
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class DeliverySelection extends SimplePageModule
{
    /**
     * Lists all available deliveries
     */
    public function index()
    {
        $service = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $context = $this->hasRequestParameter('context') ? $this->getRequestParameter('context') : null;
        $deliveries = $service->getProctorableDeliveries($proctor, $context);
        $data = array();
        foreach ($deliveries as $delivery) {
            $executions = $service->getProctorableDeliveryExecutions($proctor, $delivery, $context);
            $deliveryData = DeliveryHelper::buildDeliveryData($delivery, $executions);
            $deliveryData['url'] = _url('index', 'Monitor', null, is_null($context)
                ? ['delivery' => $delivery->getUri()]
                : ['delivery' => $delivery->getUri(), 'context' => $context]
            );
            $data[] = $deliveryData;
        }
        
        $this->composeView(
            'delivery-index',
            array(
                'testcenter' => 1234567,
                'list' => $data,
                'categories' => []
            )
        );
    }
}

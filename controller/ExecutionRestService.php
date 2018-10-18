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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoProctoring\controller;

use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;

/**
 * Manipulate with executions over REST
 */
class ExecutionRestService extends \tao_actions_RestController
{

    /**
     * Allows to resume terminated/finished executions
     */
    public function unstop()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_POST) {
                throw new \common_exception_NotImplemented('Only POST method is accepted to request this service.');
            }

            if (!$this->hasRequestParameter('deliveryExecution')) {
                $this->returnFailure(new \common_exception_MissingParameter('At least one mandatory parameter was required but found missing in your request'));
            }

            $reason = 'Automatically unstopped by REST call';

            if ($this->hasRequestParameter('reason')) {
                $reason = $this->getRequestParameter('reason');
            }

            /** @var  DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
            $deliveryExecutionManagerService = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);

            $deliveryExecution = $deliveryExecutionManagerService->getDeliveryExecutionById($this->getRequestParameter('deliveryExecution'));
            if (!$deliveryExecution->exists()) {
                throw new \common_exception_NotFound('Delivery Execution not found');
            }
            $result = $deliveryExecutionStateService->reactivateExecution($deliveryExecution, $reason);
            if ($result) {
                $this->returnSuccess('Unstop successful');
            } else {
                throw new \common_Exception('Impossible to restore execution state');
            }

        } catch (\Exception $ex) {
            $this->returnFailure($ex);
        }

    }

}


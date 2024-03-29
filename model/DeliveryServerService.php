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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoProctoring\model;

use common_exception_NotFound;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecution as DeliveryDeliveryExecution;

/**
 * Service to manage the execution of deliveries
 *
 * @access public
 * @author Aleh Hutnikau Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class DeliveryServerService extends \oat\taoDelivery\model\execution\DeliveryServerService
{
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\execution\DeliveryServerService::getResumableStates()
     */
    public function getResumableStates()
    {
        return array_merge(
            parent::getResumableStates(),
            [
                DeliveryExecution::STATE_AWAITING
                ,DeliveryExecution::STATE_AUTHORIZED
            ]
        );
    }

    /**
     * @param DeliveryDeliveryExecution $deliveryExecution
     * @throws common_exception_NotFound
     * @throws InvalidServiceManagerException
     */
    public function revoke(DeliveryDeliveryExecution $deliveryExecution)
    {
        if ($deliveryExecution->getState()->getUri() !== DeliveryExecution::STATE_PAUSED) {
            /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
            //do not remove these comments, this is used to generate the translation in .po file
            // __('Assessment has been paused due to attempt to switch to another window/tab.');
            $deliveryExecutionStateService->pauseExecution(
                $deliveryExecution,
                [
                    'reasons' => ['category' => 'focus-loss'],
                    'comment' => 'Assessment has been paused due to attempt to switch to another window/tab.',
                ]
            );
        }
    }
}

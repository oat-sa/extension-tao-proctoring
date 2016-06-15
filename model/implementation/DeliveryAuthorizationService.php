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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoProctoring\model\implementation;

use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\DeliveryAuthorizationService as DeliveryAuthorizationServiceInterface;

class DeliveryAuthorizationService extends ConfigurableService implements DeliveryAuthorizationServiceInterface
{
    /**
     * The name of the secure key used to grant proctor authorisation.
     * If the secure key is not set, or its value is not the same with the access key,
     * the test taker must wait for proctor authorization
     */
    const SECURE_KEY_NAME = 'proctor_secure_key';

    /**
     * The name of the access key used to grant proctor authorisation.
     * If the access key is not set, or its value is not the same with the secure key,
     * the test taker must wait for proctor authorization
     */
    const ACCESS_KEY_NAME = 'proctor_access_key';

    /**
     * Grants the proctor authorization: sets the current security key into the access key.
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function grantAuthorization(DeliveryExecution $deliveryExecution)
    {
        $deliveryExecution->setState(DeliveryExecution::STATE_AUTHORIZED);
        return true;
    }

    /**
     * Revokes the proctor authorization: generates a new security key.
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function revokeAuthorization(DeliveryExecution $deliveryExecution)
    {
        $deliveryExecution->setState(DeliveryExecution::STATE_PAUSED);

        return true;
    }

    /**
     * Checks the proctor authorization: checks if the value of the access key is the same as the security key.
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function isAuthorized(DeliveryExecution $deliveryExecution)
    {
        $state = $deliveryExecution->getState()->getUri();
        return $state === DeliveryExecution::STATE_AUTHORIZED || $state === DeliveryExecution::STATE_ACTIVE;
    }
}
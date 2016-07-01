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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoProctoring\model\authorization;


use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProvider extends ConfigurableService implements AuthorizationProvider
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

    private $deliveryExecution;

    public function __construct(DeliveryExecution $deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
    }

    public function isAuthorized()
    {
        $state = $this->deliveryExecution->getState()->getUri();
        return $state === ProctoredDeliveryExecution::STATE_AUTHORIZED || $state === ProctoredDeliveryExecution::STATE_ACTIVE;
    }

    public function grant()
    {
        $this->deliveryExecution->setState(ProctoredDeliveryExecution::STATE_AUTHORIZED);
        return true;
    }

    public function revoke()
    {
        $this->deliveryExecution->setState(ProctoredDeliveryExecution::STATE_PAUSED);
        return true;
    }
}

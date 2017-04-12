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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoProctoring\model\authorization;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;

/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class TestTakerAuthorizationService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/TestTakerAuthorization';

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        // always allow start
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyResumeAuthorization()
     */
    public function verifyResumeAuthorization(DeliveryExecution $deliveryExecution, User $user)
    {
        $state = $deliveryExecution->getState()->getUri();

        if (in_array($state, [
            ProctoredDeliveryExecution::STATE_FINISHED,
            ProctoredDeliveryExecution::STATE_CANCELED,
            ProctoredDeliveryExecution::STATE_TERMINATED])) {
            throw new UnAuthorizedException(
                _url('index', 'DeliveryServer', 'taoProctoring'),
                'Terminated/Finished delivery cannot be resumed'
            );
        }
        if ($this->isProctored($deliveryExecution->getDelivery()->getUri(), $user) && $state !== ProctoredDeliveryExecution::STATE_AUTHORIZED) {
            $this->throwUnAuthorizedException($deliveryExecution);
        }
    }

    /**
     * Whenever or not a delivery execution for a given delivery
     * should be proctored
     *
     * @param string $deliveryId
     * @return boolean
     */
    public function isProctored($deliveryId, User $user)
    {
        return true;
    }

    /**
     * Throw the appropriate Exception
     *
     * @param DeliveryExecution $deliveryExecution
     * @throws UnAuthorizedException
     */
    protected function throwUnAuthorizedException(DeliveryExecution $deliveryExecution)
    {
        $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'taoProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
        throw new UnAuthorizedException($errorPage, 'Proctor authorization missing');
    }
}

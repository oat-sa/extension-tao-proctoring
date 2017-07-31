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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model\authorization;


use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\ServiceDelegator;

class TestTakerAuthorizationDelegator extends ServiceDelegator implements TestTakerAuthorizationInterface
{
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     * @param $deliveryId
     * @param User $user
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        return $this->getResponsibleService($user, $deliveryId)->verifyStartAuthorization($deliveryId, $user);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyResumeAuthorization()
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param User $user
     * @throws UnAuthorizedException
     */
    public function verifyResumeAuthorization(DeliveryExecutionInterface $deliveryExecution, User $user)
    {
        $deliveryId = $deliveryExecution->getDelivery()->getUri();
        return $this->getResponsibleService($user, $deliveryId)->verifyResumeAuthorization($deliveryExecution, $user);
    }

    /**
     * Check if delivery id proctored
     *
     * @param string $deliveryId
     * @param User $user
     * @return bool
     * @internal param core_kernel_classes_Resource $delivery
     */
    public function isProctored($deliveryId, User $user)
    {
        return $this->getResponsibleService($user, $deliveryId)->isProctored($deliveryId, $user);
    }
}

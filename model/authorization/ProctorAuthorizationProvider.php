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
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;

/**
 * Delegate the authorization to the Proctor Authorization Service
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProvider extends ConfigurableService implements AuthorizationProvider
{
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     * @param string $deliveryId
     * @param User $user
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        /** @var TestTakerAuthorizationService $service */
        $service = $this->getServiceLocator()->get(TestTakerAuthorizationService::SERVICE_ID);
        $service->verifyStartAuthorization($deliveryId, $user);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyResumeAuthorization()
     * @param DeliveryExecution $deliveryExecution
     * @param User $user
     */
    public function verifyResumeAuthorization(DeliveryExecutionInterface $deliveryExecution, User $user)
    {
        /** @var TestTakerAuthorizationService $service */
        $service = $this->getServiceLocator()->get(TestTakerAuthorizationService::SERVICE_ID);
        $service->verifyResumeAuthorization($deliveryExecution, $user);
    }
}

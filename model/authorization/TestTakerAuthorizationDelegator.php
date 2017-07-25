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
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoProctoring\model\ServicesDelegator;

class TestTakerAuthorizationDelegator extends ServicesDelegator implements TestTakerAuthorizationInterface
{
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     * @param $deliveryId
     * @param User $user
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        return $this->getResponsibleService()->verifyStartAuthorization($deliveryId, $user);
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
        return $this->getResponsibleService()->verifyResumeAuthorization($deliveryExecution, $user);
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
        return $this->getResponsibleService()->isProctored($deliveryId, $user);
    }

    /**
     * Whenever or not new deliveries should be proctored by default
     *
     * @param boolean $proctored
     * @return \oat\taoProctoring\model\authorization\TestTakerAuthorizationService
     */
    public function setProctoredByDefault($proctored)
    {
        return $this->getResponsibleService()->setProctoredByDefault($proctored);
    }

    /**
     * Listen create event for delivery
     * @param DeliveryCreatedEvent $event
     */
    public function onDeliveryCreated(DeliveryCreatedEvent $event)
    {
        return $this->getResponsibleService()->onDeliveryCreated($event);
    }

    /**
     * Listen update event for delivery
     * @param DeliveryUpdatedEvent $event
     */
    public function onDeliveryUpdated(DeliveryUpdatedEvent $event)
    {
        return $this->getResponsibleService()->onDeliveryUpdated($event);
    }
}

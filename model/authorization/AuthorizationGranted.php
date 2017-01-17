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

use oat\oatbox\event\Event;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\oatbox\user\User;
/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class AuthorizationGranted implements Event
{
    const EVENT_NAME = __CLASS__;

    /**
     * @var DeliveryExecution
     */
    private $deliveryExecution;

    /**
     * @var User
     */
    private $authorizer;

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param User $authorizer
     */
    public function __construct(DeliveryExecution $deliveryExecution, User $authorizer)
    {
        $this->deliveryExecution = $deliveryExecution;
        $this->authorizer = $authorizer;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\event\Event::getName()
     */
    public function getName()
    {
        return self::EVENT_NAME;
    }

    /**
     * @return DeliveryExecution
     */
    public function getDeliveryExecution()
    {
        return $this->deliveryExecution;
    }

    /**
     * @return User
     */
    public function getAuthorizer()
    {
        return $this->authorizer;
    }
}
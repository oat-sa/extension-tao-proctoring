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

namespace oat\taoProctoring\model;


use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;

/**
 * Service which allows to use many proctorServices according to condition
 * Class ProctorServiceDelegator
 * @package oat\taoProctoring\model
 */
class ProctorServiceDelegator extends ServiceDelegator
{
    /** @deprecated need to be used SERVICE_HANDLERS */
    const PROCTOR_SERVICE_HANDLERS = self::SERVICE_HANDLERS;

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::getProctorableDeliveries()
     */
    public function getProctorableDeliveries(User $proctor, $context = null)
    {
        return $this->getResponsibleService($proctor)->getProctorableDeliveries($proctor, $context);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::getProctorableDeliveryExecutions()
     */
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        $deliveryId = $delivery ? $delivery->getUri() : null;
        return $this->getResponsibleService($proctor, $deliveryId)->getProctorableDeliveryExecutions($proctor, $delivery, $context, $options);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::countProctorableDeliveryExecutions()
     */
    public function countProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        $deliveryId = $delivery ? $delivery->getUri() : null;
        return $this->getResponsibleService($proctor, $deliveryId)->countProctorableDeliveryExecutions($proctor, $delivery, $context, $options);
    }
}

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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoProctoring\model\implementation;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\TestSessionConnectivityStatusService as TestSessionConnectivityStatusServiceInterface;

/**
 * Service to check whether test session is online.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package oat\taoProctoring
 */
class TestSessionConnectivityStatusService extends ConfigurableService implements TestSessionConnectivityStatusServiceInterface
{
    /**
     * Whether test session is online
     * @param string $sessionId test session identifier
     * @return bool
     */
    public function isOnline($sessionId) {
        \common_Logger::w('Using of `oat\taoProctoring\model\implementation\TestSessionConnectivityStatusService::isOnline()` method which may give inaccurate result.');
        $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($sessionId);
        return $deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_ACTIVE;
    }

    /**
     * @param $sessionId
     * @return int|null
     */
    public function getLastOnline($sessionId)
    {
        if ($this->isOnline($sessionId)) {
            return microtime(true);
        }
        return null;
    }


}
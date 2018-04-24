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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoProctoring\model;

use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

/**
 * Class ProctorAssignment
 * @access public
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\taoProctoring\model
 */
class ProctorAssignment extends GroupAssignment
{

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @throws
     * @return bool
     */
    protected function verifyToken(\core_kernel_classes_Resource $delivery, User $user)
    {
        $propMaxExec = $delivery->getOnePropertyValue(new \core_kernel_classes_Property(DeliveryContainerService::PROPERTY_MAX_EXEC));
        $maxExec = is_null($propMaxExec) ? 0 : $propMaxExec->literal;

        //check Tokens
        $executions = ServiceProxy::singleton()->getUserExecutions($delivery, $user->getIdentifier());
        $executions = array_filter($executions, function ($execution) {
            return $execution->getState()->getUri() !== ProctoredDeliveryExecution::STATE_CANCELED;
        });
        $usedTokens = count($executions);
        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). "without tokens");
            return false;
        }
        return true;
    }
}

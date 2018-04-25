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

use oat\taoDeliveryRdf\model\AssignmentFactory as BaseAssignmentFactory;
use core_kernel_classes_Property;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoDeliveryRdf\model\DeliveryContainerService;

class AssignmentFactory extends BaseAssignmentFactory
{
    /**
     * @inheritdoc
     */
    protected function getDescription()
    {
        $deliveryProps = $this->delivery->getPropertiesValues(array(
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_MAX_EXEC),
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_START),
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_END),
        ));

        $propMaxExec = current($deliveryProps[DeliveryContainerService::PROPERTY_MAX_EXEC]);
        $propStartExec = current($deliveryProps[DeliveryContainerService::PROPERTY_START]);
        $propEndExec = current($deliveryProps[DeliveryContainerService::PROPERTY_END]);

        $startTime = (!(is_object($propStartExec)) or ($propStartExec=="")) ? null : $propStartExec->literal;
        $endTime = (!(is_object($propEndExec)) or ($propEndExec=="")) ? null : $propEndExec->literal;
        $maxExecs = (!(is_object($propMaxExec)) or ($propMaxExec=="")) ? 0 : $propMaxExec->literal;

        $executions = ServiceProxy::singleton()->getUserExecutions($this->delivery, $this->getUserId());
        $executions = array_filter($executions, function ($execution) {
            return $execution->getState()->getUri() !== DeliveryExecution::STATE_CANCELED;
        });
        $countExecs = count($executions);

        return $this->buildDescriptionFromData($startTime, $endTime, $countExecs, $maxExecs);
    }
}

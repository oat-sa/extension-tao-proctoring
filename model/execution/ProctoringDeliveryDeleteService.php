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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\execution;

use core_kernel_classes_Resource;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use common_report_Report as Report;

class ProctoringDeliveryDeleteService extends DeliveryDeleteService
{
    /**
     * @param core_kernel_classes_Resource $deliveryResource
     * @return array
     * @throws \common_exception_Error
     */
    protected function getDeliveryExecutions(core_kernel_classes_Resource $deliveryResource)
    {
        $executions = [];
        $serviceProxy = $this->getServiceProxy();
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        $monitoringExecutions = $deliveryMonitoringService->find([
            DeliveryMonitoringService::DELIVERY_ID => $deliveryResource->getUri()
        ]);

        foreach ($monitoringExecutions as $data){
            try{
               $rawData = $data->get();
               $executions[] = $serviceProxy->getDeliveryExecution($rawData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]);
            }catch (\Exception $exception) {
                $this->report->add(Report::createFailure($exception->getMessage()));
            }
        }

        return $executions;
    }
}
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
 *
 */

namespace oat\taoProctoring\scripts;

use common_report_Report as Report;
use oat\oatbox\action\Action;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class UpdateMonitoringCache
 * @package oat\taoProctoring\scripts
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 *
 * Run example:
 * ```
 * sudo -u www-data php index.php 'oat\taoProctoring\scripts\UpdateMonitoringCache'
 * ```
 */
class UpdateMonitoringCache implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Report
     */
    protected $report;

    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
        $deliveryClass = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');
        $deliveries = $deliveryClass->getInstances(true);

        $deliveryExecutionService = ServiceProxy::singleton();

        $this->report = new Report(
            Report::TYPE_INFO,
            'Updating of delivery monitoring cache...'
        );

        foreach ($deliveries as $delivery) {
            if ($delivery->exists()) {
                $deliveryExecutions = $deliveryExecutionService->getExecutionsByDelivery($delivery);
                foreach ($deliveryExecutions as $deliveryExecution) {
                    $data = $deliveryMonitoringService->getData($deliveryExecution);
                    $data->updateData();
                    if ($deliveryMonitoringService->partialSave($data)) {
                        $this->report->add(
                            new Report(
                                Report::TYPE_SUCCESS,
                                "Delivery execution {$deliveryExecution->getUri()} successfully updated."
                            )
                        );
                    } else {
                        $errors = $data->getErrors();
                        $errorsStr = "    " . PHP_EOL;
                        array_walk($errors, function ($val, $key) use (&$errorsStr) {
                            $errorsStr .= "    $key - $val" . PHP_EOL;
                        });
                        $this->report->add(
                            new Report(
                                Report::TYPE_ERROR,
                                "Delivery execution {$deliveryExecution->getUri()} was not updated. $errorsStr"
                            )
                        );
                    }
                }
            }
        }

        return $this->report;
    }
}
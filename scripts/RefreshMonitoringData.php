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

namespace oat\taoProctoring\scripts;

use common_report_Report as Report;
use oat\oatbox\action\Action;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\implementation\DeliveryService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\taoProctoring\model\TestCenterService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class UpdateMonitoringCache
 * @package oat\taoProctoring\scripts
 *
 * Run example:
 * ```
 * sudo -u www-data php index.php 'oat\taoProctoring\scripts\RefreshMonitoringData'
 * ```
 */
class RefreshMonitoringData implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoQtiTest');
        
        $report = new Report(
            Report::TYPE_INFO,
            'Updating of delivery monitoring cache...'
        );
        
        $testCenters = TestCenterService::singleton()->getRootClass()->getInstances(true);
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::CONFIG_ID);

        $deliveryService = $this->getServiceLocator()->get(DeliveryService::CONFIG_ID);
        $eligibilityService = $this->getServiceLocator()->get(EligibilityService::SERVICE_ID);

        foreach ($testCenters as $testCenter) {
            $deliveries = $eligibilityService->getEligibleDeliveries($testCenter, false);

            foreach ($deliveries as $delivery) {
                if ($delivery->exists()) {
                    $deliveryExecutions = $deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $testCenter->getUri());
                    foreach ($deliveryExecutions as $deliveryExecution) {
                        $data = $deliveryMonitoringService->getData($deliveryExecution);
                        $data->updateData();
                        if ($deliveryMonitoringService->partialSave($data)) {
                            $report->add(
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
                            $report->add(
                                new Report(
                                    Report::TYPE_ERROR,
                                    "Delivery execution {$deliveryExecution->getUri()} was not updated. $errorsStr"
                                )
                            );
                        }
                    }
                }
            }
        }
        
        return $report;
    }
}

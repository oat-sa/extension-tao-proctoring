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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 * 
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model\export\implementation;


use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoOutcomeUi\model\ResultsService;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\export\ExporterInterface;
use oat\taoProctoring\model\implementation\DeliveryService;
use tao_helpers_Date;

class DeliveryMonitoringExporter implements ExporterInterface
{
    /**
     * @var DeliveryService
     */
    private $deliveryService;

    /**
     * @var EligibilityService
     */
    private $eligibilityService;

    /**
     * @var ResultsService
     */
    private $resultsService;
    
    public function __construct(DeliveryService $deliveryService, EligibilityService $eligibilityService, ResultsService $resultsService)
    {
        $this->deliveryService = $deliveryService;
        $this->eligibilityService = $eligibilityService;
        $this->resultsService = $resultsService;
    }
    
    /**
     * @param \core_kernel_classes_Resource $testCenter
     * @param \core_kernel_classes_Resource $delivery
     * @return array|\oat\taoDelivery\model\execution\DeliveryExecution[]
     *  [
     *    'testTakerId' => [
     *      'nb_item' => int,
     *      'nb_executions' => int,
     *      'nb_finished' => int
     *    ], ...
     *  ]
     */
    public function getData(\core_kernel_classes_Resource $testCenter, \core_kernel_classes_Resource $delivery = null)
    {
        $exportResult = [];

        $deliveryExecutions = $delivery
            ? $this->deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $testCenter->getUri())
            : $this->getTestCenterExecutions($testCenter);
        
        // sort all executions by reverse date
        usort($deliveryExecutions, function ($a, $b) {
            return -strcmp(tao_helpers_Date::getTimeStamp($a->getStartTime()), tao_helpers_Date::getTimeStamp($b->getStartTime()));
        });


        foreach ($deliveryExecutions as $deliveryExecution) {

            $userId = $deliveryExecution->getUserIdentifier();
            if (!isset($exportResult[$userId])) {
                
                $userResource = new \core_kernel_classes_Resource($userId);
                $user = new \core_kernel_users_GenerisUser($userResource);
                $login = current($user->getPropertyValues(PROPERTY_USER_LOGIN));
                
                $exportResult[$userId] = [
                    'test_taker' => $login,
                    'nb_item' => 0,
                    'nb_executions' => 0,
                    'nb_finished' => 0
                ];
            }
            
            $rowResult = &$exportResult[$userId];
            $rowResult['nb_executions']++;
            
            if ($deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_FINISHIED) {
                $rowResult['nb_finished']++;
            }
            
            $rowResult['nb_item'] += $this->countFinishedItems($deliveryExecution);
        }
        
        return $exportResult;
    }

    private function getTestCenterExecutions(\core_kernel_classes_Resource $testCenter)
    {
        $deliveries = $this->eligibilityService->getEligibleDeliveries($testCenter);

        $all = [];
        foreach ($deliveries as $delivery) {
            if ($delivery->exists()) {
                $all = array_merge($all, $this->deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $testCenter->getUri()));
            }
        }

        return $all;
    }
    
    private function countFinishedItems(DeliveryExecution $deliveryExecution)
    {
        $implementation = $this->resultsService->getReadableImplementation($deliveryExecution->getDelivery());
        $this->resultsService->setImplementation($implementation);
        $itemCallIds = $this->resultsService->getItemResultsFromDeliveryResult($deliveryExecution);
        return count($itemCallIds);
    }
}

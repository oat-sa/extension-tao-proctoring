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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\model;

use \oat\taoOutcomeUi\model\ResultsService;
use \oat\oatbox\service\ConfigurableService;

/**
 * Class AssessmentResultsService
 *
 * Service to retrieve session data such as results, test taker data, execution time and so on.
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class AssessmentResultsService extends ConfigurableService
{
    const CONFIG_ID = 'taoProctoring/AssessmentResults';

    /**
     * Get test taker data as associative array
     * @param \taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution
     * @return array
     */
    public function getTestTakerData(\taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution)
    {
        $data = $this->getResultService($deliveryExecution->getDelivery())->getTestTakerData($deliveryExecution);
        $result = $this->propertiesToArray($data);
        return $result;
    }

    /**
     * Get test data as associative array
     * @param \taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution
     * @return array
     */
    public function getTestData(\taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution)
    {
        $resultService = $this->getResultService($deliveryExecution->getDelivery());
        $testUri = $resultService->getTestsFromDeliveryResult($deliveryExecution);
        $testResource = new \core_kernel_classes_Resource($testUri[0]);
        $propValues = $testResource->getPropertiesValues(array(
            RDFS_LABEL,
        ));
        $result = $this->propertiesToArray($propValues);

        $deliveryVariables = $resultService->getVariableDataFromDeliveryResult($deliveryExecution);
        $result = array_merge($result, $this->variablesToArray($deliveryVariables));

        return $result;
    }

    /**
     * Get session results
     * @param \taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution
     * @return array
     */
    public function getResultsData(\taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution)
    {
        $result = [];
        $resultService = $this->getResultService($deliveryExecution->getDelivery());

        $itemsData = $resultService->getItemVariableDataFromDeliveryResult($deliveryExecution, 'lastSubmitted');

        foreach($itemsData as $itemData) {
            $rawResult = [];
            $rawResult['label'] = $itemData['label'];
            foreach ($itemData['sortedVars'] as $variables) {
                 $variableValues = array_map(function ($variable) {
                    $variable = current($variable);
                    return $variable['var']->getValue();
                 }, $variables);
                 $rawResult = array_merge($rawResult, $variableValues);
            };
            $result[] = $rawResult;
        }
        return $result;
    }

    /**
     * Get delivery execution data
     * @param \taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution
     * @return array
     */
    public function getDeliveryData(\taoDelivery_models_classes_execution_DeliveryExecution $deliveryExecution)
    {
        $result = [
            'start' => $deliveryExecution->getStartTime(),
            'end' => $deliveryExecution->getFinishTime(),
            'label' => $deliveryExecution->getLabel()
        ];
        return $result;
    }

    /**
     * Get result service instance.
     * @return \oat\taoOutcomeUi\model\ResultsService;
     */
    protected function getResultService(\core_kernel_classes_Resource $delivery)
    {
        $resultsService = ResultsService::singleton();
        $implementation = $resultsService->getReadableImplementation($delivery);
        $resultsService->setImplementation($implementation);
        return $resultsService;
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function propertiesToArray($properties)
    {
        $result = [];
        foreach ($properties as $uri => $item) {
            $resource = new \core_kernel_classes_Resource($uri);
            if ($resource->exists() && isset($item[0])) {
                $result[$resource->getLabel()] = (string) $item[0];
            }
        }
        return $result;
    }

    /**
     * @param array $variables list of variables
     * @return array
     */
    protected function variablesToArray($variables)
    {
        $result = [];
        foreach ($variables as $variable) {
            $result[$variable->getIdentifier()] = $variable->getValue();
        }
        return $result;
    }
}
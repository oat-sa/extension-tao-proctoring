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

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution as DeliveryExecutionInterface;
use oat\taoOutcomeUi\model\ResultsService;
use oat\taoProctoring\model\implementation\TestSessionService;
use qtism\data\View;

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
    /** @deprecated */
    const CONFIG_ID = 'taoProctoring/AssessmentResults';

    const SERVICE_ID = 'taoProctoring/AssessmentResults';

    const OPTION_PRINTABLE_RUBRIC_TAG = 'printable_rubric_tag';
    const OPTION_PRINT_REPORT_BUTTON = 'print_report_button';

    /**
     * Get test taker data as associative array
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     */
    public function getTestTakerData(DeliveryExecutionInterface $deliveryExecution)
    {
        $data = $this->getResultService($deliveryExecution->getDelivery())->getTestTakerData($deliveryExecution->getIdentifier());
        $result = $this->propertiesToArray($data);
        return $result;
    }

    /**
     * Get test data as associative array
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     */
    public function getTestData(DeliveryExecutionInterface $deliveryExecution)
    {
        $resultService = $this->getResultService($deliveryExecution->getDelivery());
        $testUri = $resultService->getTestsFromDeliveryResult($deliveryExecution->getIdentifier());
        $testResource = new \core_kernel_classes_Resource($testUri[0]);
        $propValues = $testResource->getPropertiesValues(array(
            RDFS_LABEL,
        ));
        $result = $this->propertiesToArray($propValues);

        $deliveryVariables = $resultService->getVariableDataFromDeliveryResult($deliveryExecution->getIdentifier());
        $result = array_merge($result, $this->variablesToArray($deliveryVariables));

        return $result;
    }

    /**
     * Get session results
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     */
    public function getResultsData(DeliveryExecutionInterface $deliveryExecution)
    {
        $result = [];
        $resultService = $this->getResultService($deliveryExecution->getDelivery());

        $itemsData = $resultService->getItemVariableDataFromDeliveryResult($deliveryExecution->getIdentifier(), 'lastSubmitted');

        foreach ($itemsData as $itemData) {
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
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     */
    public function getDeliveryData(DeliveryExecutionInterface $deliveryExecution)
    {
        $result = [
            'start' => $deliveryExecution->getStartTime(),
            'end' => $deliveryExecution->getFinishTime(),
            'label' => $deliveryExecution->getLabel()
        ];
        return $result;
    }


    /**
     * Get rubric to be printed
     * Rubric is considered printed if it included to the section which has an item tagged by specified tag
     * (@see )
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return string
     */
    public function getPrintableRubric(DeliveryExecutionInterface $deliveryExecution)
    {
        $testSessionService = ServiceManager::getServiceManager()->get(TestSessionService::SERVICE_ID);
        $session = $testSessionService->getTestSession($deliveryExecution);

        $inputParameters = $testSessionService->getRuntimeInputParameters($deliveryExecution);
        $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
        $sections = $testDefinition->getComponentsByClassName('assessmentSection');

        $tag = $this->getOption(self::OPTION_PRINTABLE_RUBRIC_TAG);

        $directoryIds = explode('|', $inputParameters['QtiTestCompilation']);
        $compilationDirs = array(
            'private' => \tao_models_classes_service_FileStorage::singleton()->getDirectoryById($directoryIds[0]),
            'public' => \tao_models_classes_service_FileStorage::singleton()->getDirectoryById($directoryIds[1]),
        );

        $rubrics = [];

        // -- variables used in the included rubric block templates.
        // base path (base URI to be used for resource inclusion).
        $basePathVarName = TAOQTITEST_BASE_PATH_NAME;
        $$basePathVarName = $compilationDirs['public']->getPublicAccessUrl();

        // state name (the variable to access to get the state of the assessmentTestSession).
        $stateName = TAOQTITEST_RENDERING_STATE_NAME;
        $$stateName = $session;

        // views name (the variable to be accessed for the visibility of rubric blocks).
        $viewsName = TAOQTITEST_VIEWS_NAME;
        $$viewsName = array(View::CANDIDATE);

        foreach ($sections as $section) {
            $assessmentItemsRef = $section->getComponentsByClassName('assessmentItemRef');
            foreach ($assessmentItemsRef as $item) {
                foreach($item->getCategories() as $category) {
                    if ($category === $tag) {
                        foreach ($section->getRubricBlockRefs() as $rubric) {
                            ob_start();
                            include $compilationDirs['private']->getPath() . '/' . ltrim($rubric->getHref(), './\\');
                            $rubrics[] = ob_get_clean();
                        }
                    }
                };
            };
        }

        return $rubrics;
    }

    /**
     * Get result service instance.
     * @param \core_kernel_classes_Resource $delivery
     * @return ResultsService
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

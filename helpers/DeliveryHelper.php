<?php
/*
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
 * Copyright (c) 2015-2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\helpers;

use core_kernel_classes_Resource;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\DeliveryExecution as DeliveryExecutionInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\ReasonCategoryService;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use qtism\runtime\tests\AssessmentTestSessionState;
use tao_helpers_Date as DateHelper;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class DeliveryHelper
{
    /**
     * Cached value for prepopulated fields
     * @var array
     */
    private static $extraFields = [];

    /**
     * Translation map to convert frontend data column into database column
     * @var array
     */
    private static $columnsMap = [
        'delivery' => DeliveryMonitoringService::DELIVERY_NAME,
        'deliveryLabel' => DeliveryMonitoringService::DELIVERY_NAME,
    ];

    /**
     * @return \oat\oatbox\service\ConfigurableService
     */
    private static function getDeliveryExecutionManagerService()
    {
        return ServiceManager::getServiceManager()->get(DeliveryExecutionManagerService::SERVICE_ID);
    }

    /**
     * Creates a standard error message with different actions
     * @param {DeliveryExecution} $deliveryExecution
     * @param {String} $action
     * @return string
     */
    private static function createErrorMessage($deliveryExecution, $action)
    {
        if ($deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_FINISHED) {
            $errorMsg = __('%s could not be %s because it is finished. Please refresh your data.', $deliveryExecution->getLabel(), $action);
        } else if ($deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_TERMINATED) {
            $errorMsg = __('%s could not be %s because it is terminated. Please refresh your data.', $deliveryExecution->getLabel(), $action);
        } else {
            $errorMsg = __('%s could not be %s.', $deliveryExecution->getLabel(), $action);
        }

        return $errorMsg;
    }

    public static function buildDeliveryData($delivery, $executions)
    {
        $inprogress = 0;
        $paused = 0;
        $awaiting = 0;
        foreach($executions as $executionData) {
            $executionState = $executionData[DeliveryMonitoringService::STATUS];
            switch($executionState){
                case DeliveryExecution::STATE_AWAITING:
                    $awaiting++;
                    break;
                case DeliveryExecution::STATE_ACTIVE:
                    $inprogress++;
                    break;
                case DeliveryExecution::STATE_PAUSED:
                    $paused++;
                    break;
            }
        }

        $deliveryProps = array(
            new \core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_START),
            new \core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_END),
        );
        $deliveryProperties = $delivery->getPropertiesValues($deliveryProps);
        $propStartExec = current($deliveryProperties[DeliveryAssemblyService::PROPERTY_START]);
        $propEndExec = current($deliveryProperties[DeliveryAssemblyService::PROPERTY_END]);

        $properties = array();
        if (!is_null($propStartExec) && !empty((string)$propStartExec)) {
            $properties['periodStart'] = DateHelper::displayeDate((string)$propStartExec);
        }
        if (!is_null($propStartExec) && !empty((string)$propEndExec)) {
            $properties['periodEnd'] = DateHelper::displayeDate((string)$propEndExec);
        }

        $entry = array(
            'id' => $delivery->getUri(),
            'url' => _url('monitoring', 'Delivery', null, array('delivery' => $delivery->getUri())),
            'label' => $delivery->getLabel(),
            'stats' => array(
                'awaitingApproval' => $awaiting,
                'inProgress' => $inprogress,
                'paused' => $paused
            ),
            'properties' => $properties
        );

        return $entry;
    }

    /**
     * Gets the aggregated data for a filtered set of delivery executions of a given delivery
     * This is performance critical, would need to find a way to optimize to obtain such information
     *
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $testCenter
     * @param array $options
     * @return array
     */
    public static function getCurrentDeliveryExecutions(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $testCenter, array $options = array())
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        return self::adjustDeliveryExecutions($deliveryService->getCurrentDeliveryExecutions($delivery, $testCenter, $options), $options);
    }

    /**
     * Authorises a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @param string $testCenter Test center uri
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function authoriseExecutions($deliveryExecutions, $reason = null, $testCenter = null)
    {
        /** @var  DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = [ 'processed' => [], 'unprocessed' => [] ];
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }

            if ($deliveryExecutionStateService->authoriseExecution($deliveryExecution, $reason, $testCenter)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = self::createErrorMessage($deliveryExecution, __('authorized'));
            }
        }

        return $result;
    }

    /**
     * Terminates a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function terminateExecutions($deliveryExecutions, $reason = null)
    {
        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = [ 'processed' => [], 'unprocessed' => [] ];
        foreach ($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }

            if ($deliveryExecutionStateService->terminateExecution($deliveryExecution, $reason)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = self::createErrorMessage($deliveryExecution, __('terminated'));
            }
        }

        return $result;
    }

    /**
     * @param array $deliveryExecutions
     * @param null $reason
     * @return array
     */
    public static function reactivateExecution($deliveryExecutions, $reason = null)
    {
        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = [ 'processed' => [], 'unprocessed' => [] ];
        foreach ($deliveryExecutions as $deliveryExecution) {

            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }

            if ($deliveryExecutionStateService->reactivateExecution($deliveryExecution, $reason)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = self::createErrorMessage($deliveryExecution, __('terminated'));
            }
        }

        return $result;
    }

    /**
     * Pauses a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function pauseExecutions($deliveryExecutions, $reason = null)
    {
        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = [ 'processed' => [], 'unprocessed' => [] ];
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }

            try {
                $isPaused = $deliveryExecutionStateService->pauseExecution($deliveryExecution, $reason);
                if ($isPaused) {
                    $result['processed'][$deliveryExecution->getIdentifier()] = true;
                } else {
                    $result['unprocessed'][$deliveryExecution->getIdentifier()] = self::createErrorMessage($deliveryExecution, __('paused'));
                }
            } catch (\Exception $exception) {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = $exception->getMessage();
            }
        }

        return $result;
    }

    /**
     * Report irregularity to a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function reportExecutions($deliveryExecutions, $reason = null)
    {
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = [ 'processed' => [], 'unprocessed' => [] ];
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }

            if ($deliveryExecutionStateService->reportExecution($deliveryExecution, $reason)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = self::createErrorMessage($deliveryExecution, __('reported for irregularity'));
            }
        }

        return $result;
    }

    /**
     * Gets the delivery time counter
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return QtiTimer
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getDeliveryTimer($deliveryExecution)
    {
        return self::getDeliveryExecutionManagerService()->getDeliveryTimer($deliveryExecution);
    }

    /**
     * Sets the extra time to a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param float $extraTime
     * @param float $extendedTime
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function setExtraTime($deliveryExecutions, $extraTime = null, $extendedTime = null)
    {
        return self::getDeliveryExecutionManagerService()->setExtraTime($deliveryExecutions, $extraTime, $extendedTime);
    }

    public static function getDeliveryExecutionById($deliveryExecutionId)
    {
        return self::getDeliveryExecutionManagerService()->getDeliveryExecutionById($deliveryExecutionId);
    }

    public static function buildDeliveryExecutionData($deliveryExecutions, $sortOptions = array()) {
        return self::adjustDeliveryExecutions($deliveryExecutions, $sortOptions);
    }

    /**
     * Converts a frontend column name into database column name.
     * Useful to translate the name of a column to sort.
     * @param string $column
     * @return string
     */
    public static function adjustColumnName($column)
    {
        if (isset(self::$columnsMap[$column])) {
            $column = self::$columnsMap[$column];
        }
        return $column;
    }

    /**
     * Adjusts a list of delivery executions: add information, format the result
     * @param DeliveryExecution[] $deliveryExecutions
     * @return array
     * @internal param array $options
     */
    private static function adjustDeliveryExecutions($deliveryExecutions) {

        /** @var TestSessionConnectivityStatusService $testSessionConnectivityStatusService */
        $testSessionConnectivityStatusService = ServiceManager::getServiceManager()->get(TestSessionConnectivityStatusService::SERVICE_ID);

        $executions = [];
        foreach ($deliveryExecutions as $cachedData) {

            $state = [
                'status' => $cachedData[DeliveryMonitoringService::STATUS],
                'progress' => $cachedData[DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM]
            ];

            $testTaker = [];
            $extraFields = [];

            /* @var $user User */
            $testTaker['id'] = $cachedData[DeliveryMonitoringService::TEST_TAKER];
            $testTaker['test_taker_last_name'] = (isset($cachedData[DeliveryMonitoringService::TEST_TAKER_LAST_NAME]))?_dh($cachedData[DeliveryMonitoringService::TEST_TAKER_LAST_NAME]):'';
            $testTaker['test_taker_first_name'] = (isset($cachedData[DeliveryMonitoringService::TEST_TAKER_FIRST_NAME]))?_dh($cachedData[DeliveryMonitoringService::TEST_TAKER_FIRST_NAME]):'';

            foreach(self::_getUserExtraFields() as $field){
                $extraFields[$field['id']] = isset($cachedData[$field['id']]) ? _dh($cachedData[$field['id']]) : '';
            }

            $online = null;
            if ($testSessionConnectivityStatusService->hasOnlineMode()) {
                $rawConnectivity = isset($cachedData[DeliveryMonitoringService::CONNECTIVITY]) ? $cachedData[DeliveryMonitoringService::CONNECTIVITY] : false;
                $online = $testSessionConnectivityStatusService->isOnline($cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID], $rawConnectivity);
            }

            if (isset($cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY]) && $online) {
                $lastActivity = $cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY];
            } else {
                $lastActivity = null;
            }

            $executionState = $cachedData[DeliveryMonitoringService::STATUS];
            $extraTime = (isset($cachedData[DeliveryMonitoringService::EXTRA_TIME])) ? floatval($cachedData[DeliveryMonitoringService::EXTRA_TIME]) : 0;
            $remaining = self::getRemainingTime($cachedData);
            $approximatedRemaining = self::getApproximatedRemainingTime($cachedData, $online, $lastActivity);

            $execution = array(
                'id' => $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID],
                'delivery' => array(
                    'uri' => $cachedData[DeliveryMonitoringService::DELIVERY_ID],
                    'label' => _dh($cachedData[DeliveryMonitoringService::DELIVERY_NAME]),
                ),
                'start_time' => $cachedData[DeliveryMonitoringService::START_TIME],
                'allowExtraTime' => (isset($cachedData[DeliveryMonitoringService::ALLOW_EXTRA_TIME])) ? boolval($cachedData[DeliveryMonitoringService::ALLOW_EXTRA_TIME]) : null,
                'timer' => [
                    'lastActivity' => $lastActivity,
                    'countDown' => (DeliveryExecution::STATE_ACTIVE == $executionState && $online) ? true : false,
                    'approximatedRemaining' => $approximatedRemaining,
                    'remaining_time' => $remaining,
                    'extraTime' => $extraTime,
                    'extendedTime' => (isset($cachedData[DeliveryMonitoringService::EXTENDED_TIME]) && $cachedData[DeliveryMonitoringService::EXTENDED_TIME] > 1) ? floatval($cachedData[DeliveryMonitoringService::EXTENDED_TIME]) : '',
                    'consumedExtraTime' => (isset($cachedData[DeliveryMonitoringService::CONSUMED_EXTRA_TIME])) ? floatval($cachedData[DeliveryMonitoringService::CONSUMED_EXTRA_TIME]) : 0
                ],
                'testTaker' => $testTaker,
                'extraFields' => $extraFields,
                'state' => $state,
            );

            if ($online) {
                $execution['online'] = $online;
            }

            $executions[] = $execution;
        }

        return $executions;
    }

    private static function getRemainingTime(array $cachedData)
    {
        $remaining  = (isset($cachedData[DeliveryMonitoringService::REMAINING_TIME])) ? intval($cachedData[DeliveryMonitoringService::REMAINING_TIME]) : 0;
        return $remaining;
    }

    private static function getApproximatedRemainingTime(array $cachedData, $online)
    {
        $now = microtime(true);
        $remaining = self::getRemainingTime($cachedData);
        $elapsedApprox = 0;
        $executionState = $cachedData[DeliveryMonitoringService::STATUS];

        if (
            isset($cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY]) &&
            $executionState === DeliveryExecution::STATE_ACTIVE
        ) {
            $lastActivity = $cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY];
            $elapsedApprox = $now - $lastActivity;
            $duration = (isset($cachedData[DeliveryMonitoringService::ITEM_DURATION])) ? floatval($cachedData[DeliveryMonitoringService::ITEM_DURATION]) : 0;
            $elapsedApprox += $duration;
        }

        if (is_bool($online) && $online === false) {
            $elapsedApprox = 0;
        }

        $approximatedRemaining = round(floatval($remaining) - $elapsedApprox);
        return $approximatedRemaining;
    }

    /**
     * Get array of user specific extra fields to be displayed in the monitoring data table
     *
     * @return array
     */
    private static function _getUserExtraFields(){
        if (!self::$extraFields){
            $proctoringExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
            $userExtraFields = $proctoringExtension->getConfig('monitoringUserExtraFields');
            $userExtraFieldsSettings = $proctoringExtension->getConfig('monitoringUserExtraFieldsSettings');
            if(!empty($userExtraFields) && is_array($userExtraFields)){
                foreach($userExtraFields as $name => $uri){
                    $property = new \core_kernel_classes_Property($uri);
                    $settings = array_key_exists($name, $userExtraFieldsSettings) ?
                        $userExtraFieldsSettings[$name] : [];
                    self::$extraFields[] = array_merge(array(
                        'id' => $name,
                        'property' => $property,
                        'label' => $property->getLabel(),
                    ), $settings);
                }
            }
        }

        return self::$extraFields;
    }

    /**
     * Return array of extra fields to be displayed in the monitoring data table
     *
     * @return array
     */
    public static function getExtraFields(){
        return array_map(function($field){
            $extra = [
                'id' => $field['id'],
                'label' => $field['label'],
                'filterable' => array_key_exists('filterable', $field) ? $field['filterable'] : false,
            ];
            if (array_key_exists('columnPosition', $field)) {
                $extra['columnPosition'] = $field['columnPosition'];
            }
            return $extra;
        }, self::_getUserExtraFields());
    }

    /**
     * Return array of extra fields to be saved in monitoring storage
     *
     * @return array
     */
    public static function getExtraFieldsProperties(){
        return array_map(function($field){
            return array(
                'id' => $field['id'],
                'property' => $field['property']
            );
        }, self::_getUserExtraFields());
    }

     /**
     * Catch changing of session state
     * @param QtiTestStateChangeEvent $event
     */
    public static function testStateChanged(QtiTestStateChangeEvent $event)
    {
        if ($event->getPreviousState() !== AssessmentTestSessionState::INITIAL
            && $event->getSession()->getState() === AssessmentTestSessionState::SUSPENDED) {
            self::setHasBeenPaused($event->getSession()->getSessionId(), true);
        }
    }

    /**
     * @param $deliveryExecution
     * @return mixed
     */
    public static function getHasBeenPaused($deliveryExecution)
    {
        if (is_string($deliveryExecution)) {
            $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
        }
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $data = $deliveryMonitoringService->getData($deliveryExecution);
        $status = isset($data->get()['hasBeenPaused']) ? (boolean) $data->get()['hasBeenPaused'] : false;
        self::setHasBeenPaused($deliveryExecution, false);
        return $status;
    }

    /**
     * @param $deliveryExecution
     * @param boolean $paused
     * @throws \common_exception_NotFound
     */
    public static function setHasBeenPaused($deliveryExecution, $paused)
    {
        if (is_string($deliveryExecution)) {
            $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
        }
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);

        $data = $deliveryMonitoringService->createMonitoringData($deliveryExecution);

        $data->update('hasBeenPaused', $paused);
        $deliveryMonitoringService->partialSave($data);
    }

    /**
     * Get the list of all available categories, sorted by action names
     *
     * @param bool $hasAccessToReactivate
     * @return array
     */
    public static function getAllReasonsCategories($hasAccessToReactivate = false){
        /** @var ReasonCategoryService $categoryService */
        $categoryService = ServiceManager::getServiceManager()->get(ReasonCategoryService::SERVICE_ID);

        $response = array(
            'authorize' => array(),
            'pause' => $categoryService->getIrregularities(),
            'terminate' => $categoryService->getIrregularities(),
            'report' => $categoryService->getIrregularities(),
            'print' => [],
        );
        if ($hasAccessToReactivate) {
            $response['reactivate'] = $categoryService->getIrregularities();
        }

        return $response;
    }
}

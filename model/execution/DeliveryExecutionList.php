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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoProctoring\model\execution;

use common_ext_ExtensionsManager;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\tao\model\service\ApplicationService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\event\DeliveryLogEvent;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoQtiTest\models\SessionStateService;

/**
 * Class DeliveryHelperService
 * @author Bartlomiej Marszal
 */
class DeliveryExecutionList extends ConfigurableService
{
    use OntologyAwareTrait;

    /**
     * Adjusts a list of delivery executions: add information, format the result
     * @param DeliveryExecution[] $deliveryExecutions
     * @return array
     * @throws \common_ext_ExtensionException
     * @throws \common_exception_Error
     * @internal param array $options
     */
    public function adjustDeliveryExecutions($deliveryExecutions)
    {
        $executions = [];
        $userExtraFields = $this->getUserExtraFields();

        /** @var array $cachedData */
        foreach ($deliveryExecutions as $cachedData) {
            $executions[] = $this->getSingleExecution($cachedData, $userExtraFields);
        }

        return $executions;
    }

    /**
     * @param $cachedData
     * @return array
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     */
    private function createTestTaker($cachedData)
    {
        $testTaker = [];

        /* @var $user User */
        $testTaker['id'] = $cachedData[DeliveryMonitoringService::TEST_TAKER];
        $testTaker['test_taker_last_name'] = isset($cachedData[DeliveryMonitoringService::TEST_TAKER_LAST_NAME])
            ? $this->sanitizeUserInput($cachedData[DeliveryMonitoringService::TEST_TAKER_LAST_NAME])
            : '';
        $testTaker['test_taker_first_name'] = isset($cachedData[DeliveryMonitoringService::TEST_TAKER_FIRST_NAME])
            ? $this->sanitizeUserInput($cachedData[DeliveryMonitoringService::TEST_TAKER_FIRST_NAME])
            : '';

        return $testTaker;
    }

    /**
     * @param $cachedData
     * @param $extraFields
     * @return array
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     */
    private function createExecution($cachedData, $extraFields)
    {
        $online = $this->isOnline($cachedData);

        $isTimerAdjustmentAllowed = $this->getDeliveryExecutionManagerService()->isTimerAdjustmentAllowed(
            $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
        );

        $executionState = $cachedData[DeliveryMonitoringService::STATUS];

        $timerAdjustmentAllowed = $this->getDeliveryExecutionManagerService()->isTimerAdjustmentAllowed(
            $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
        );

        $execution = array(
            'id' => $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID],
            'delivery' => array(
                'uri' => $cachedData[DeliveryMonitoringService::DELIVERY_ID],
                'label' => $this->sanitizeUserInput($cachedData[DeliveryMonitoringService::DELIVERY_NAME]),
            ),
            'start_time' => $cachedData[DeliveryMonitoringService::START_TIME],
            'allowExtraTime' => isset($cachedData[DeliveryMonitoringService::ALLOW_EXTRA_TIME])
                ? (bool)$cachedData[DeliveryMonitoringService::ALLOW_EXTRA_TIME]
                : null,
            'allowTimerAdjustment' => $isTimerAdjustmentAllowed,
            'timer' => [
                'lastActivity' => $this->getLastActivity($cachedData, $online),
                'countDown' => DeliveryExecution::STATE_ACTIVE === $executionState && $online,
                'approximatedRemaining' => $this->getApproximatedRemainingTime($cachedData, $online),
                'remaining_time' => $this->getRemainingTime($cachedData),
                'extraTime' => (float) ($cachedData[DeliveryMonitoringService::EXTRA_TIME] ?? 0),
                'extendedTime' => (isset($cachedData[DeliveryMonitoringService::EXTENDED_TIME]) && $cachedData[DeliveryMonitoringService::EXTENDED_TIME] > 1)
                    ? (float)$cachedData[DeliveryMonitoringService::EXTENDED_TIME]
                    : '',
                'consumedExtraTime' => (float) ($cachedData[DeliveryMonitoringService::CONSUMED_EXTRA_TIME] ?? 0),
            ],
            'testTaker' => $this->createTestTaker($cachedData),
            'extraFields' => $extraFields,
            'state' => $this->createState($cachedData),
        );

        if ($this->isOnline($cachedData)) {
            $execution['online'] = $online;
        }

        if ($isTimerAdjustmentAllowed) {
            $reason = $this->getLastPauseReason($cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]);
            if ($reason) {
                $execution['lastPauseReason'] = $reason;
            }

            $execution['timer']['timeAdjustmentLimits'] = [
                'decrease' => $this->getDeliveryExecutionManagerService()->getTimerAdjustmentDecreaseLimit(
                    $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
                ),
                'increase' => $this->getDeliveryExecutionManagerService()->getTimerAdjustmentIncreaseLimit(
                    $cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
                ),
            ];
        }

        return $execution;
    }

    private function getLastPauseReason(string $deliveryExecutionId): ?array
    {
        $reason = null;
        $lastPause = $this->getDeliveryLogService()->search([
            DeliveryLog::DELIVERY_EXECUTION_ID => $deliveryExecutionId,
            DeliveryLog::EVENT_ID => DeliveryLogEvent::EVENT_ID_TEST_PAUSE,
        ], [
            'order' => 'created_at',
            'dir' => 'desc',
            'limit' => 1,
        ]);

        if (isset($lastPause[0][DeliveryLog::DATA]['reason'])) {
            $reason = $lastPause[0][DeliveryLog::DATA]['reason'];
        }

        return $reason;
    }

    /**
     * @return DeliveryLog|object
     */
    private function getDeliveryLogService(): DeliveryLog
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * @param $cachedData
     * @param $userExtraFields
     * @return array
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     */
    private function getSingleExecution($cachedData, $userExtraFields)
    {
        $extraFields = [];
        foreach ($userExtraFields as $field) {
            $extraFields[$field['id']] = $this->getFieldId($cachedData, $field);
        }

        return $this->createExecution($cachedData, $extraFields);
    }

    /**
     * @param $cachedData
     * @param $field
     * @return string
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     */
    private function getFieldId($cachedData, $field)
    {
        $value = isset($cachedData[$field['id']])
            ? $this->sanitizeUserInput($cachedData[$field['id']])
            : '';
        if (\common_Utils::isUri($value)) {
            $value = $this->getResource($value)->getLabel();
        }
        return $value;
    }

    /**
     * @param array $cachedData
     * @param $online
     * @return float
     */
    private function getApproximatedRemainingTime(array $cachedData, $online)
    {
        $now = microtime(true);
        $remaining = $this->getRemainingTime($cachedData);
        $elapsedApprox = 0;
        $executionState = $cachedData[DeliveryMonitoringService::STATUS];

        if (
            $executionState === DeliveryExecution::STATE_ACTIVE
            && isset($cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY])
        ) {
            $lastActivity = (float)$cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY];
            $elapsedApprox = $now - $lastActivity;
            $duration = (float)($cachedData[DeliveryMonitoringService::ITEM_DURATION] ?? 0);
            $duration -= (float)($cachedData[DeliveryMonitoringService::STORED_ITEM_DURATION] ?? 0);
            $elapsedApprox += $duration;
        }

        if (is_bool($online) && $online === false) {
            $elapsedApprox = 0;
        }

        return round((float)$remaining - $elapsedApprox);
    }

    /**
     * @param array $cachedData
     * @return int
     */
    private function getRemainingTime(array $cachedData)
    {
        return (int) ($cachedData[DeliveryMonitoringService::REMAINING_TIME] ?? 0);
    }


    /**
     * Get array of user specific extra fields to be displayed in the monitoring data table
     *
     * @return array
     * @throws \common_ext_ExtensionException
     */
    private function getUserExtraFields()
    {
        $proctoringExtension = $this->getExtensionManagerService()->getExtensionById('taoProctoring');
        $userExtraFields = $proctoringExtension->getConfig('monitoringUserExtraFields');
        if (empty($userExtraFields) || !is_array($userExtraFields)) {
            return [];
        }

        $userExtraFieldsSettings = $proctoringExtension->getConfig('monitoringUserExtraFieldsSettings');

        $extraFields = [];
        foreach ($userExtraFields as $name => $uri) {
            $extraFields[] = $this->mergeExtraFieldsSettings($uri, $name, $userExtraFieldsSettings);
        }

        return $extraFields;
    }

    private function mergeExtraFieldsSettings($uri, $name, $userExtraFieldsSettings)
    {
        $property = $this->getProperty($uri);
        $settings = array_key_exists($name, $userExtraFieldsSettings)
            ? $userExtraFieldsSettings[$name]
            : [];

        return array_merge([
            'id' => $name,
            'property' => $property,
            'label' => __($property->getLabel()),
        ], $settings);
    }

    /**
     * @param array $cachedData
     * @return mixed|string
     */
    private function getProgressString(array $cachedData)
    {
        $progressStr = $cachedData[DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM];
        $progress = json_decode($progressStr, true);
        if ($progress === null) {
            return $progressStr;
        }

        if (in_array($cachedData[DeliveryMonitoringService::STATUS], [DeliveryExecutionInterface::STATE_TERMINATED, DeliveryExecutionInterface::STATE_FINISHED], true)) {
            return $progress['title'];
        }
        $format = $this->getSessionStateService()->hasOption(SessionStateService::OPTION_STATE_FORMAT)
            ? $this->getSessionStateService()->getOption(SessionStateService::OPTION_STATE_FORMAT)
            : __('%s - item %p/%c');
        $map = array(
            '%s' => $progress['title'] ?? '',
            '%p' => $progress['itemPosition'] ?? '',
            '%c' => $progress['itemCount'] ?? ''
        );

        return strtr($format, $map);
    }

    /**
     * @param array $cachedData
     * @return bool|null
     */
    private function isOnline(array $cachedData)
    {
        if ($this->getTestSessionConnectivityStatusService()->hasOnlineMode()) {
            return $this->getTestSessionConnectivityStatusService()->isOnline($cachedData[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]);
        }
        return null;
    }

    /**
     * @param array $cachedData
     * @param bool|null $online
     * @return float|null
     */
    private function getLastActivity(array $cachedData, ?bool $online)
    {
        if ($online && isset($cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY])) {
            return $cachedData[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY];
        }

        return null;
    }

    /**
     * @param $input
     * @return string
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     */
    private function sanitizeUserInput($input)
    {
        return htmlentities($input, ENT_COMPAT, $this->getApplicationService()->getDefaultEncoding());
    }

    /**
     * @return ApplicationService
     */
    private function getApplicationService()
    {
        return $this->getServiceLocator()->get(ApplicationService::SERVICE_ID);
    }

    /**
     * @return TestSessionConnectivityStatusService
     */
    private function getTestSessionConnectivityStatusService()
    {
        return $this->getServiceLocator()->get(TestSessionConnectivityStatusService::SERVICE_ID);
    }

    /**
     * @return SessionStateService
     */
    private function getSessionStateService()
    {
        return $this->getServiceLocator()->get(SessionStateService::SERVICE_ID);
    }

    /**
     * @return common_ext_ExtensionsManager
     */
    private function getExtensionManagerService()
    {
        return $this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID);
    }

    /**
     * @return DeliveryExecutionManagerService
     */
    private function getDeliveryExecutionManagerService()
    {
        return $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);
    }

    /**
     * @param $cachedData
     * @return array
     */
    private function createState($cachedData): array
    {
        $progressStr = $this->getProgressString($cachedData);

        $state = [
            'status' => $cachedData[DeliveryMonitoringService::STATUS],
            'progress' => __($progressStr)
        ];
        return $state;
    }
}

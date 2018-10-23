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

namespace oat\taoProctoring\model\monitorCache\implementation;

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\time\QtiTimerFactory;
use oat\taoTests\models\runner\time\TimePoint;
use qtism\runtime\tests\AssessmentTestSession;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryMonitoringData implements DeliveryMonitoringDataInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var DeliveryExecution
     */
    private $deliveryExecution;

    /**
     * @var AssessmentTestSession
     */
    private $testSession;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $requiredFields = [
        DeliveryMonitoringService::DELIVERY_EXECUTION_ID,
        DeliveryMonitoringService::STATUS,
    ];

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $data
     * @throws \common_exception_NotFound
     */
    public function __construct(DeliveryExecutionInterface $deliveryExecution, array $data)
    {
        $this->deliveryExecution = $deliveryExecution;
        $this->data = $data;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\monitorCache\DeliveryMonitoringData::update()
     */
    public function update($key, $value)
    {
       $this->addValue($key, $value, true);
    }

    /**
     * Add data
     * @param string $key
     * @param string $value
     * @param boolean $overwrite
     */
    public function addValue($key, $value, $overwrite = false)
    {
        if (!isset($this->data[$key]) || $overwrite) {
            $this->data[$key] = (string) $value;
        }
    }

    /**
     * Save delivery execution
     * @param DeliveryExecution $deliveryExecution
     */
    public function setDeliveryExecution(DeliveryExecution $deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
    }

    /**
     * Validate data
     * @return bool whether data is valid and can be saved.
     */
    public function validate()
    {
        $result = true;
        $this->errors = [];
        $data = $this->get();

        foreach ($this->requiredFields as $requiredField) {
            if (!isset($data[$requiredField])) {
                $result = false;
                $this->errors[$requiredField] = 'cannot be empty';
            }
        }

        foreach ($data as $fieldName => $fieldValue) {
            if (!array_key_exists($fieldName, $this->errors) && $fieldValue !== null && !is_string($fieldValue)) {
                $this->errors[$fieldName] = 'should be a string';
            }
        }

        return $result;
    }

    /**
     * Get list of errors.
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get delivery execution data
     * @param bool $refresh
     * @return array
     */
    public function get($refresh = false)
    {
        if ($refresh) {
            $this->updateData();
        }
        return $this->data;
    }

    /**
     * Set test session
     * @param AssessmentTestSession $testSession
     */
    public function setTestSession(AssessmentTestSession $testSession)
    {
        $this->testSession = $testSession;
    }

    /**
     * @param array $keys
     */
    public function updateData(array $keys = null)
    {
        if ($keys === null) {
            $keys = [
                DeliveryMonitoringService::STATUS,
                DeliveryMonitoringService::REMAINING_TIME,
                DeliveryMonitoringService::EXTRA_TIME,
                DeliveryMonitoringService::EXTENDED_TIME
            ];
        }
        foreach ($keys as $key) {
            $methodName = 'update' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $methodName)) {
                $this->{$methodName}();
            }
        }
    }

    /**
     * Update extra time allowed for the delivery execution
     */
    private function updateLastTestTakerActivity()
    {
        $this->addValue(DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY, microtime(true), true);
    }

    /**
     * Update connectivity status (online|offline)
     */
    private function updateLastConnect()
    {
        $status = $this->deliveryExecution->getState()->getUri();
        /** @var TestSessionConnectivityStatusService $testSessionConnectivityStatusService */
        $testSessionConnectivityStatusService = $this->getServiceLocator()->get(TestSessionConnectivityStatusService::SERVICE_ID);

        if ($testSessionConnectivityStatusService->hasOnlineMode() && ProctoredDeliveryExecution::STATE_ACTIVE == $status) {
            $lastConnectivity = $testSessionConnectivityStatusService->getLastOnline($this->deliveryExecution->getIdentifier());
        }else{
            // to ensure that during sorting by connectivity all similar statuses grouped together
            $lastConnectivity = (~PHP_INT_MAX) + substr(abs(crc32($status)), 0, 3);
        }

        $this->addValue(DeliveryMonitoringService::CONNECTIVITY, $lastConnectivity, true);
    }

    /**
     * Update test session state
     */
    private function updateStatus()
    {
        $status = $this->deliveryExecution->getState()->getUri();
        $this->addValue(DeliveryMonitoringService::STATUS, $status, true);
        if ($status == ProctoredDeliveryExecution::STATE_PAUSED) {
            $this->addValue(DeliveryMonitoringService::LAST_PAUSE_TIMESTAMP, microtime(true), true);
        }
    }

    /**
     * Update remaining time of delivery execution
     */
    private function updateRemainingTime()
    {
        $result = null;
        $remaining = 0;
        $hasTimer = false;

        $session = $this->getTestSession();

        if ($session !== null && $session->isRunning()) {
            $remaining = PHP_INT_MAX;
            $timeConstraints = $session->getTimeConstraints();
            foreach ($timeConstraints as $tc) {
                // Only consider time constraints in force.
                $maximumRemainingTime = $tc->getMaximumRemainingTime();
                if ($maximumRemainingTime !== false) {
                    $hasTimer = true;
                    $remaining = min($remaining, $maximumRemainingTime->getSeconds(true));
                }
            }
        }

        if ($hasTimer) {
            $result = $remaining;
        }

        $this->addValue(DeliveryMonitoringService::REMAINING_TIME, $result, true);
    }

    /**
     * Update diff between last_pause_timestamp and last_test_taker_activity
     */
    private function updateDiffTimestamp()
    {
        $diffTimestamp = 0;
        $lastTimeStamp = 0;
        $lastActivity = 0;
        if (isset($this->data[DeliveryMonitoringService::LAST_PAUSE_TIMESTAMP])) {
            $lastTimeStamp = $this->data[DeliveryMonitoringService::LAST_PAUSE_TIMESTAMP];
        }

        if (isset($this->data[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY])) {
            $lastActivity = $this->data[DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY];
        }

        if ($lastTimeStamp - $lastActivity > 0) {
            $diffTimestamp = isset($this->data[DeliveryMonitoringService::DIFF_TIMESTAMP]) ? $this->data[DeliveryMonitoringService::DIFF_TIMESTAMP] : 0;
            $diffTimestamp += $lastTimeStamp - $lastActivity;
        }

        $this->addValue(DeliveryMonitoringService::DIFF_TIMESTAMP, $diffTimestamp, true);
    }

    /**
     * Update extra time allowed for the delivery execution
     */
    private function updateExtraTime()
    {
        $testSession = $this->getTestSession();
        if ($testSession instanceof TestSession) {
            $timer = $testSession->getTimer();
            $timerTarget = $testSession->getTimerTarget();
        } else {
            $timerTarget = TimePoint::TARGET_SERVER;
            $qtiTimerFactory = $this->getServiceLocator()->get(QtiTimerFactory::SERVICE_ID);
            $timer = $qtiTimerFactory->getTimer($this->deliveryExecution->getIdentifier(), $this->deliveryExecution->getUserIdentifier());
        }

        $deliveryExecutionManager = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);
        $maxTimeSeconds = $deliveryExecutionManager->getTimeLimits($testSession);

        $data = $this->get();
        $oldConsumedExtraTime = isset($data[DeliveryMonitoringService::CONSUMED_EXTRA_TIME]) ? $data[DeliveryMonitoringService::CONSUMED_EXTRA_TIME] : 0;
        $consumedExtraTime = max($oldConsumedExtraTime, $timer->getConsumedExtraTime(null, $maxTimeSeconds, $timerTarget));

        $this->addValue(DeliveryMonitoringService::EXTRA_TIME, $timer->getExtraTime($maxTimeSeconds), true);
        $this->addValue(DeliveryMonitoringService::CONSUMED_EXTRA_TIME, $consumedExtraTime, true);
    }

    /**
     * @return AssessmentTestSession
     */
    private function getTestSession()
    {
        if ($this->testSession === null) {
            $testSessionService = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
            $this->testSession = $testSessionService->getTestSession($this->deliveryExecution);
        }
        return $this->testSession;
    }
}

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

use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;
use qtism\runtime\tests\AssessmentTestSession;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * class DeliveryMonitoringData
 *
 * Represents data model of delivery execution.
 *
 * @package oat\taoProctoring
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryMonitoringData implements DeliveryMonitoringDataInterface
{
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
        DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID,
        DeliveryMonitoringService::COLUMN_STATUS,
    ];

    /**
     * DeliveryMonitoringData constructor.
     * @param DeliveryExecution $deliveryExecution
     * @param boolean $updateData whether instance should be populated by data during instantiation.
     */
    public function __construct(DeliveryExecution $deliveryExecution, $updateData = true)
    {
        $this->deliveryExecution = $deliveryExecution;

        $deliveryExecutionId = $this->deliveryExecution->getIdentifier();

        $data = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID)->find([
            [DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => $deliveryExecutionId],
        ], ['asArray' => true], true);

        if (empty($data)) {
            $this->addValue('delivery_execution_id', $deliveryExecutionId);
        } else {
            $this->data = $data[0];
        }

        if ($updateData) {
            $this->updateData();
        }
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
            $this->data[$key] = $value;
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
     * Set test session
     * @param AssessmentTestSession $testSession
     */
    public function setTestSession(AssessmentTestSession $testSession)
    {
        $this->testSession = $testSession;
        $this->updateData();
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
     * @return array
     */
    public function get($refresh = false)
    {
        if (empty($this->data) || $refresh) {
            $this->updateData();
        }
        return $this->data;
    }

    private function updateData()
    {
        $this->addValue(DeliveryMonitoringService::STATUS, $this->getStatus(), true);
        $this->addValue(DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM, $this->getProgress(), true);
        $this->addValue(DeliveryMonitoringService::TEST_TAKER, $this->getTestTaker(), true);
        $this->addValue(DeliveryMonitoringService::COLUMN_AUTHORIZED_BY, $this->getAuthorizedBy(), true);
        $this->addValue(DeliveryMonitoringService::START_TIME, $this->getStartTime(), true);
        $this->addValue(DeliveryMonitoringService::END_TIME, $this->getEndTime(), true);
    }

    /**
     * @return string
     */
    private function getStatus()
    {
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        return $deliveryExecutionStateService->getState($this->deliveryExecution);
    }

    /**
     * @return string
     */
    private function getProgress()
    {
        $session = $this->getTestSession();
        $result = null;
        if ($session !== null) {
            $pos = $session->getRoute()->getPosition();
            $count = $session->getRouteCount();

            if ($session->isRunning()) {
                $section = $session->getCurrentAssessmentSection();
                $result =  __('%1$s - item %2$s/%3$s', $section->getTitle(), $pos+1, $count);
            } else {
                $result = __('finished');
            }
        }
        return $result;
    }

    /**
     * return AssessmentTestSession
     */
    private function getTestSession()
    {
        if ($this->testSession === null) {
            $testSessionService = TestSessionService::singleton();
            $this->testSession = $testSessionService->getTestSession($this->deliveryExecution);
        }
        return $this->testSession;
    }

    /**
     * @return string
     */
    private function getTestTaker()
    {
        return $this->deliveryExecution->getUserIdentifier();
    }

    /**
     * @return null|string
     */
    private function getAuthorizedBy()
    {
        $result = null;
        $deliveryLog = $this->getDeliveryLog('TEST_AUTHORISE');
        if (!empty($deliveryLog) && isset($deliveryLog[0]['data']['proctorUri'])) {
            $result = $deliveryLog[0]['data']['proctorUri'];
        }
        return $result;

    }

    /**
     * @return string
     */
    private function getStartTime()
    {
        list($usec, $sec) = explode(" ", $this->deliveryExecution->getStartTime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * @return string
     */
    private function getEndTime()
    {
        $finishTime = $this->deliveryExecution->getFinishTime();
        if ($finishTime) {
            list($usec, $sec) = explode(" ", $this->deliveryExecution->getFinishTime());
            return ((float)$usec + (float)$sec);
        }
        return '';
    }

    /**
     * @return string
     */
    private function getDeliveryLog($eventId = null)
    {
        $deliveryLogService = ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
        return $deliveryLogService->get($this->deliveryExecution->getIdentifier(), $eventId);
    }
}
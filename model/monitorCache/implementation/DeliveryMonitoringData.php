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

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData as DeliveryMonitoringDataInterface;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\QtiTimeStorage;
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
     * DeliveryMonitoringData constructor.
     * @param DeliveryExecutionInterface $deliveryExecution
     */
    public function __construct(DeliveryExecutionInterface $deliveryExecution, $data)
    {
        $this->deliveryExecution = $deliveryExecution;
        if (is_array($data) && !empty($data)) {
            $this->data = $data;
        } else {
            $this->data = [DeliveryMonitoringService::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()];
        }
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
            if ($session instanceof TestSession) {
                $timeConstraints = $session->getRegularTimeConstraints();
            } else {
                $timeConstraints = $session->getTimeConstraints();
            }
            foreach ($timeConstraints as $tc) {
                // Only consider time constraints in force.
                if ($tc->getMaximumRemainingTime() !== false) {
                    $hasTimer = true;
                    $remaining = min($remaining, $tc->getMaximumRemainingTime()->getSeconds(true));
                }
            }
        }

        if ($hasTimer) {
            $result = $remaining;
        }

        $this->addValue(DeliveryMonitoringService::REMAINING_TIME, $result, true);
    }
    
    /**
     * Update extra time allowed for the delivery execution
     */
    private function updateExtraTime()
    {
        $testSession = $this->getTestSession();
        if ($testSession instanceof TestSession) {
            $timer = $testSession->getTimer();
        } else {
            $timer = new QtiTimer();
            $timer->setStorage(new QtiTimeStorage($this->deliveryExecution->getIdentifier(), $this->deliveryExecution->getUserIdentifier()));
            $timer->load();
        }
        $maxTimeSeconds = null;

        if ($item = $testSession->getCurrentAssessmentItemRef()) {
            if ($testSessionLimits = $item->getTimeLimits()) {
                $maxTimeSeconds = $testSessionLimits->hasMaxTime()
                    ? $testSessionLimits->getMaxTime()->getSeconds(true)
                    : $maxTimeSeconds;
            }
        }

        if (!$maxTimeSeconds && $section = $testSession->getCurrentAssessmentSection()) {
            if ($testSessionLimits = $section->getTimeLimits()) {
                $maxTimeSeconds = $testSessionLimits->hasMaxTime()
                    ? $testSessionLimits->getMaxTime()->getSeconds(true)
                    : $maxTimeSeconds;
            }
        }

        if (!$maxTimeSeconds && $testPart = $testSession->getCurrentTestPart()) {
            if ($testSessionLimits = $testPart->getTimeLimits()) {
                $maxTimeSeconds = $testSessionLimits->hasMaxTime()
                    ? $testSessionLimits->getMaxTime()->getSeconds(true)
                    : $maxTimeSeconds;
            }
        }

        if (!$maxTimeSeconds && $assessmentTest = $testSession->getAssessmentTest()) {
            if ($assessmentTestLimits = $assessmentTest->getTimeLimits()) {
                $maxTimeSeconds = $assessmentTestLimits->hasMaxTime()
                    ? $assessmentTestLimits->getMaxTime()->getSeconds(true)
                    : $maxTimeSeconds;
            }
        }

        $this->addValue(DeliveryMonitoringService::EXTRA_TIME, $timer->getExtraTime($maxTimeSeconds), true);
        $this->addValue(DeliveryMonitoringService::CONSUMED_EXTRA_TIME, $timer->getConsumedExtraTime(), true);
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

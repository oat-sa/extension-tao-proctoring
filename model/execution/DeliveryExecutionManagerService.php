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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\execution;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\StorageManager;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\QtiTimerFactory;
use oat\taoTests\models\runner\time\TimePoint;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentServiceInterface;
use oat\taoTests\models\runner\time\TimerStrategyInterface;
use qtism\common\datatypes\QtiDuration;
use qtism\data\AssessmentTest;
use qtism\runtime\tests\AssessmentTestSessionState;

/**
 * Class DeliveryExecutionManagerService
 * @package oat\taoProctoring\model\execution
 */
class DeliveryExecutionManagerService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/DeliveryExecutionManagerService';

    /**
     * @param $deliveryExecutionId
     * @return DeliveryExecutionInterface
     */
    public function getDeliveryExecutionById($deliveryExecutionId)
    {
        return ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
    }

    /**
     * Gets the delivery time counter
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return QtiTimer
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     */
    public function getDeliveryTimer($deliveryExecution)
    {
        if (is_string($deliveryExecution)) {
            $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
        }

        $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution, true);
        if ($testSession instanceof TestSession) {
            $timer = $testSession->getTimer();
        } else {
            $qtiTimerFactory = $this->getServiceLocator()->get(QtiTimerFactory::SERVICE_ID);
            $timer = $qtiTimerFactory->getTimer($deliveryExecution->getIdentifier(), $deliveryExecution->getUserIdentifier());
        }

        return $timer;
    }

    /**
     * @param TestSession $testSession
     * @param $part
     * @return int|null
     */
    protected function getPartTimeLimits($testSession, $part)
    {
        $timeLimits = $part->getTimeLimits();
        if ($timeLimits && ($maxTime = $timeLimits->getMaxTime()) !== null) {
            if ($testSession !== null && ($timer = $testSession->getTimer()) !== null) {
                $maxTime = $this->getTimerAdjustmentService()->getAdjustedMaxTime($part, $timer);
            }
            return $maxTime->getSeconds(true);
        }
        return null;
    }

    /**
     * Gets the actual time limits for a test session
     * @param TestSession $testSession
     * @return int|null
     */
    public function getTimeLimits($testSession)
    {
        $seconds = null;

        if ($item = $testSession->getCurrentAssessmentItemRef()) {
            $seconds = $this->getPartTimeLimits($testSession, $item);
        }

        if (!$seconds && $section = $testSession->getCurrentAssessmentSection()) {
            $seconds = $this->getPartTimeLimits($testSession, $section);
        }

        if (!$seconds && $testPart = $testSession->getCurrentTestPart()) {
            $seconds = $this->getPartTimeLimits($testSession, $testPart);
        }

        if (!$seconds && $assessmentTest = $testSession->getAssessmentTest()) {
            $seconds = $this->getPartTimeLimits($testSession, $assessmentTest);
        }

        return $seconds;
    }

    /**
     * Sets the extra time to a list of delivery executions
     * @param $deliveryExecutions
     * @param int $extraTime
     * @param null $extendedTime
     * @return array
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\taoTests\models\runner\time\InvalidStorageException
     */
    public function setExtraTime($deliveryExecutions, $extraTime = 0, $extendedTime = null)
    {
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        $testSessionService = $this->getTestSessionService();

        $result = ['processed' => [], 'unprocessed' => []];

        /** @var DeliveryExecution $deliveryExecution */
        foreach ($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
            }

            /** @var DeliveryMonitoringData $data */
            $data = $deliveryMonitoringService->getData($deliveryExecution);
            $maxTime = 0;
            $timerTarget = TimePoint::TARGET_SERVER;
            if ($extendedTime) {
                $inputParameters = $testSessionService->getRuntimeInputParameters($deliveryExecution);
                /** @var AssessmentTest $testDefinition */
                $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
                $deliveryExecutionArray[] = $deliveryExecution;
                $extraTime = null;

                /* @var TestSession $testSession */
                $testSession = $testSessionService->getTestSession($deliveryExecution);
                if ($testSession) {
                    $seconds = $this->getTimeLimits($testSession);
                } else {
                    $seconds = $this->getPartTimeLimits($testSession, $testDefinition);
                }

                if ($seconds) {
                    $extraTime = $this->getServiceLocator()
                        ->get(TimerStrategyInterface::SERVICE_ID)
                        ->getExtraTime($seconds, $extendedTime);

                    $dataArray = $data->get();
                    if (!isset($dataArray[DeliveryMonitoringService::REMAINING_TIME])) {
                        $data->update(DeliveryMonitoringService::REMAINING_TIME, $seconds);
                    }
                }
            }

            // reopen the execution if already closed
            if ($deliveryExecution->getState()->getUri() == DeliveryExecution::STATE_FINISHED) {
                $deliveryExecution->setState(DeliveryExecution::STATE_ACTIVE);

                /* @var TestSession $testSession */
                $testSession = $testSessionService->getTestSession($deliveryExecution);

                if ($testSession) {
                    $timerTarget = $testSession->getTimerTarget();
                    $testSession->getRoute()->setPosition(0);
                    $testSession->setState(AssessmentTestSessionState::INTERACTING);

                    // The duration store contains durations (time spent) on test, testPart(s) and assessmentSection(s).
                    $durationStore = $testSession->getDurationStore();

                    $offsetDuration = new QtiDuration("PT${extraTime}S");
                    $testDefinition = $testSession->getAssessmentTest();
                    $currentDuration = $durationStore[$testDefinition->getIdentifier()];

                    $offsetSeconds = $offsetDuration->getSeconds(true);
                    $currentSeconds = $currentDuration->getSeconds(true);
                    $newSeconds = $currentSeconds - $offsetSeconds;

                    if ($newSeconds < 0) {
                        $newSeconds = 0;
                    }

                    // Replace test duration with new duration.
                    $durationStore[$testDefinition->getIdentifier()] = new QtiDuration("PT${newSeconds}S");

                    $testSessionService->persist($testSession);
                    $maxTime = $this->getPartTimeLimits($testSession, $testDefinition);
                }
            }

            /** @var QtiTimer $timer */
            $timer = $this->getDeliveryTimer($deliveryExecution);
            $timer
                ->setExtraTime($extraTime)
                ->setExtendedTime($extendedTime)
                ->save();

            $data->update(DeliveryMonitoringService::EXTRA_TIME, $timer->getExtraTime());
            $data->update(DeliveryMonitoringService::EXTENDED_TIME, $timer->getExtendedTime());
            $data->update(DeliveryMonitoringService::CONSUMED_EXTRA_TIME, $timer->getConsumedExtraTime(null, $maxTime, $timerTarget));
            if ($deliveryMonitoringService->save($data)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = false;
            }
        }

        $this->getServiceLocator()->get(StorageManager::SERVICE_ID)->persist();

        return $result;
    }

    /**
     * Registers timer adjustments to a list of delivery executions
     * @param array $deliveryExecutions
     * @param int $seconds
     * @return array
     */
    public function adjustTimers(array $deliveryExecutions, $seconds)
    {
        $result = ['processed' => [], 'unprocessed' => []];

        $timerAdjustmentService = $this->getTimerAdjustmentService();

        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        /** @var DeliveryExecution $deliveryExecution */
        foreach ($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
            }

            $success = false;
            if ($this->isTimerAdjustmentAllowed($deliveryExecution)) {
                $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution);
                if ($seconds > 0) {
                    $success = $timerAdjustmentService->increase($testSession, $seconds);
                } else {
                    $success = $timerAdjustmentService->decrease($testSession, abs($seconds));
                }

                $data = $deliveryMonitoringService->getData($deliveryExecution);
                $data->updateData([DeliveryMonitoringService::REMAINING_TIME]);
                $deliveryMonitoringService->save($data);
            }

            if ($success) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = false;
            }
        }

        return $result;
    }

    /**
     * @param DeliveryExecutionInterface|string $deliveryExecution
     * @return bool
     */
    public function isTimerAdjustmentAllowed($deliveryExecution)
    {
        if (is_string($deliveryExecution)) {
            $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
        }

        if ($deliveryExecution->getState()->getUri() !== DeliveryExecution::STATE_AWAITING) {
            return false;
        }

        if (!$this->getTestSessionService()->getTestSession($deliveryExecution)) {
            return false;
        }

        return true;
    }

    /**
     * @return TestSessionService
     */
    private function getTestSessionService()
    {
        return $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
    }

    /**
     * @return TimerAdjustmentServiceInterface
     */
    private function getTimerAdjustmentService()
    {
        return $this->getServiceLocator()->get(TimerAdjustmentServiceInterface::SERVICE_ID);
    }
}

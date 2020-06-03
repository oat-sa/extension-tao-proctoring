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

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use common_session_Session;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\session\SessionService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\StorageManager;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\QtiTimerFactory;
use oat\taoTests\models\runner\time\TimePoint;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentServiceInterface;
use oat\taoTests\models\runner\time\TimerStrategyInterface;
use qtism\common\datatypes\QtiDuration;
use qtism\data\AssessmentTest;
use qtism\data\QtiIdentifiable;
use qtism\runtime\tests\AssessmentTestSessionState;

/**
 * Class DeliveryExecutionManagerService
 * @package oat\taoProctoring\model\execution
 */
class DeliveryExecutionManagerService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/DeliveryExecutionManagerService';

    protected CONST NO_TIME_ADJUSTMENT_LIMIT = -1;

    private $deliveryExecutions = [];

    /**
     * @param $deliveryExecutionId
     * @return DeliveryExecutionInterface
     */
    public function getDeliveryExecutionById($deliveryExecutionId)
    {
        if (!isset($this->deliveryExecutions[$deliveryExecutionId])) {
            $this->deliveryExecutions[$deliveryExecutionId] = ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
        }

        return $this->deliveryExecutions[$deliveryExecutionId];
    }

    /**
     * Gets the delivery time counter
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return QtiTimer
     * @throws common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws common_exception_NotFound
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
     * @return array
     * @throws common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws common_exception_NotFound
     * @throws \oat\taoTests\models\runner\time\InvalidStorageException
     */
    public function setExtraTime($deliveryExecutions, $extraTime = 0)
    {
        $deliveryMonitoringService = $this->getDeliveryMonitoringService();

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
                ->save();

            $data->update(DeliveryMonitoringService::EXTRA_TIME, $timer->getExtraTime());
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
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $extendedTime
     * @throws common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws common_exception_NotFound
     * @throws \oat\taoTests\models\runner\time\InvalidStorageException
     */
    public function updateDeliveryExtendedTime(DeliveryExecutionInterface $deliveryExecution, $extendedTime)
    {
        $timer = $this->getDeliveryTimer($deliveryExecution);
        if ($timer->getExtendedTime()) {
            return;
        }

        $inputParameters = $this->getTestSessionService()->getRuntimeInputParameters($deliveryExecution);
        /** @var AssessmentTest $testDefinition */
        $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
        $components = $testDefinition->getComponentsByClassName(['testPart', 'assessmentSection', 'assessmentItemRef']);
        $components->attach($testDefinition);

        /** @var QtiIdentifiable $component */
        foreach ($components as $component) {
            $timeLimits = $component->getTimeLimits();
            if ($timeLimits && $timeLimits->hasMaxTime()) {
                $currentLimitSeconds = $timeLimits->getMaxTime()->getSeconds(true);
                $increaseSeconds = $this->getServiceLocator()
                    ->get(TimerStrategyInterface::SERVICE_ID)
                    ->getExtraTime($currentLimitSeconds, $extendedTime);
                if ($increaseSeconds > 0) {
                    $timer->getAdjustmentMap()->increase(
                        $component->getIdentifier(),
                        TimerAdjustmentServiceInterface::TYPE_EXTENDED_TIME,
                        $increaseSeconds
                    );
                }
            }
        }
        $timer->setExtendedTime($extendedTime);
        $timer->save();
        $this->getServiceLocator()->get(StorageManager::SERVICE_ID)->persist();

        $deliveryMonitoringService = $this->getDeliveryMonitoringService();
        $data = $deliveryMonitoringService->getData($deliveryExecution);
        $data->update(DeliveryMonitoringService::EXTENDED_TIME, $timer->getExtendedTime());
        $deliveryMonitoringService->save($data);
    }

    /**
     * Registers timer adjustments to a list of delivery executions
     * @param array $deliveryExecutions
     * @param int $seconds
     * @param array $reason
     * @return array
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     */
    public function adjustTimers(array $deliveryExecutions, $seconds, array $reason = []): array
    {
        $result = ['processed' => [], 'unprocessed' => []];

        $timerAdjustmentService = $this->getTimerAdjustmentService();
        $deliveryMonitoringService = $this->getDeliveryMonitoringService();

        /** @var common_session_Session $session */
        $session = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();
        $proctor = $session->getUser();

        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        /** @var DeliveryExecution $deliveryExecution */
        foreach ($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
            }

            $success = false;
            if ($this->isTimerAdjustmentAllowed($deliveryExecution)) {

                $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution);
                if ($seconds > 0) {
                    $success = $timerAdjustmentService->increase($testSession, $seconds, TimerAdjustmentServiceInterface::TYPE_TIME_ADJUSTMENT);
                } else {
                    $success = $timerAdjustmentService->decrease($testSession, abs($seconds), TimerAdjustmentServiceInterface::TYPE_TIME_ADJUSTMENT);
                }

                $data = $deliveryMonitoringService->getData($deliveryExecution);
                $data->updateData([DeliveryMonitoringService::REMAINING_TIME]);

                $deliveryMonitoringService->save($data);

                $eventManager->trigger(new DeliveryExecutionTimerAdjusted($deliveryExecution, $proctor, $seconds, $reason));
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
     * @param string $deliveryExecutionId
     * @return int
     */
    public function getTimerAdjustmentDecreaseLimit(string $deliveryExecutionId): int
    {
        $decreaseLimit = self::NO_TIME_ADJUSTMENT_LIMIT;
        try {
            $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecutionId);
            $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution);
            $currentTimer = $this->getTestSessionService()->getSmallestMaxTimeConstraint($testSession);
            $decreaseLimit = $currentTimer->getMaximumRemainingTime()->getSeconds(true);
        } catch (common_Exception $e) {
            $this->logError("Cannot calculate minimum time adjustment limit.");
        }

        return $decreaseLimit;
    }

    /**
     * @param string $deliveryExecutionId
     * @return int
     */
    public function getTimerAdjustmentIncreaseLimit(string $deliveryExecutionId): int
    {
        return self::NO_TIME_ADJUSTMENT_LIMIT;
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

    /**
     * @return DeliveryMonitoringService
     */
    private function getDeliveryMonitoringService()
    {
        return $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
    }
}

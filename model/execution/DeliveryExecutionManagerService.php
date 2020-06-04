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

declare(strict_types=1);

namespace oat\taoProctoring\model\execution;

use common_Exception;
use common_exception_Error;
use common_exception_MissingParameter;
use common_exception_NotFound;
use common_ext_ExtensionException;
use common_session_Session;
use Exception;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\session\SessionService;
use oat\taoDelivery\model\execution\DeliveryExecution as BaseDeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\StorageManager;
use oat\taoQtiTest\models\runner\time\QtiTimeConstraint;
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\QtiTimerFactory;
use oat\taoQtiTest\models\runner\time\TimerAdjustmentService;
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
    public const SERVICE_ID = 'taoProctoring/DeliveryExecutionManagerService';

    protected const NO_TIME_ADJUSTMENT_LIMIT = -1;

    private $deliveryExecutions = [];

    /**
     * @param $deliveryExecutionId
     * @return BaseDeliveryExecution
     */
    public function getDeliveryExecutionById($deliveryExecutionId): BaseDeliveryExecution
    {
        if (!isset($this->deliveryExecutions[$deliveryExecutionId])) {
            $deliveryExecution = $this->getServiceProxy()
                ->getDeliveryExecution($deliveryExecutionId);
            $this->deliveryExecutions[$deliveryExecutionId] = $deliveryExecution;
        }

        return $this->deliveryExecutions[$deliveryExecutionId];
    }

    /**
     * @return ServiceProxy|object
     */
    private function getServiceProxy()
    {
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * Gets the delivery time counter
     *
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return QtiTimer
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
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
     * @throws common_exception_MissingParameter
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
     * @throws common_exception_MissingParameter
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
                $increaseSeconds = (int) $this->getServiceLocator()
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
    public function adjustTimers(array $deliveryExecutions, int $seconds, array $reason = []): array
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
                $success = $this->adjustDeliveryExecutionTimer($seconds, $deliveryExecution, $timerAdjustmentService);

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
     * @param $seconds
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param TimerAdjustmentServiceInterface $timerAdjustmentService
     * @return bool
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_Exception
     */
    protected function adjustDeliveryExecutionTimer(
        $seconds,
        DeliveryExecutionInterface $deliveryExecution,
        TimerAdjustmentServiceInterface $timerAdjustmentService
    ): bool {
        $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution);
        if ($seconds > 0) {
            $success = $timerAdjustmentService->increase(
                $testSession,
                $seconds,
                TimerAdjustmentServiceInterface::TYPE_TIME_ADJUSTMENT
            );
        } else {
            $success = $timerAdjustmentService->decrease(
                $testSession,
                abs($seconds),
                TimerAdjustmentServiceInterface::TYPE_TIME_ADJUSTMENT
            );
        }
        return $success;
    }

    /**
     * @param DeliveryExecutionInterface|string $deliveryExecution
     * @return bool
     */
    public function isTimerAdjustmentAllowed($deliveryExecution): bool
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
            $currentTimeConstraint = $this->getSmallestMaxTimeConstraint($deliveryExecutionId);
            $decreaseLimit = $currentTimeConstraint->getMaximumRemainingTime()->getSeconds(true);
        } catch (Exception $e) {
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
     * Returns timerAdjustment for the timer with smaller value for the current item/section/testPart/test chain
     * @param string $deliveryExecutionId
     * @return int
     * @throws QtiTestExtractionFailedException
     */
    public function getAdjustedTime(string $deliveryExecutionId): int
    {
        $adjustedTime = 0;
        try {
            $currentTimeConstraint = $this->getSmallestMaxTimeConstraint($deliveryExecutionId);
            if ($currentTimeConstraint) {
                $adjustedTime = $this->getTimerAdjustmentService()->getAdjustmentByType(
                    $currentTimeConstraint->getSource(),
                    $currentTimeConstraint->getTimer(),
                    TimerAdjustmentService::TYPE_TIME_ADJUSTMENT
                );
            }
        } catch (Exception $e) {
            $this->logError("Cannot calculate adjusted time for provided execution ID: {$deliveryExecutionId}.");
        }

        return $adjustedTime;
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

    /**
     * @param string $deliveryExecutionId
     * @return QtiTimeConstraint
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_Exception
     */
    protected function getSmallestMaxTimeConstraint(string $deliveryExecutionId): QtiTimeConstraint
    {
        $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecutionId);
        $testSession = $this->getTestSessionService()->getTestSession($deliveryExecution);

        if (!$testSession) {
            throw new common_Exception('Test Session not found');
        }

        return $this->getTestSessionService()->getSmallestMaxTimeConstraint($testSession);
    }
}

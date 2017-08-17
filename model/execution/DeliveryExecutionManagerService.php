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
use oat\taoQtiTest\models\runner\time\QtiTimer;
use oat\taoQtiTest\models\runner\time\QtiTimeStorage;
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
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function getDeliveryTimer($deliveryExecution)
    {
        if (is_string($deliveryExecution)) {
            $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
        }

        /** @var TestSessionService $testSessionService */
        $testSessionService = $this->getServiceManager()->get(TestSessionService::SERVICE_ID);

        $testSession = $testSessionService->getTestSession($deliveryExecution);
        if ($testSession instanceof TestSession) {
            $timer = $testSession->getTimer();
        } else {
            $timer = new QtiTimer();
            $timer->setStorage(new QtiTimeStorage($deliveryExecution->getIdentifier(), $deliveryExecution->getUserIdentifier()));
            $timer->load();
        }

        return $timer;
    }

    /**
     * Sets the extra time to a list of delivery executions
     * @param $deliveryExecutions
     * @param null $extraTime
     * @param null $extendedTime
     * @return array
     */
    public function setExtraTime($deliveryExecutions, $extraTime = null, $extendedTime = null)
    {
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);

        $result = ['processed' => [], 'unprocessed' => []];

        /** @var DeliveryExecution $deliveryExecution */
        foreach ($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = $this->getDeliveryExecutionById($deliveryExecution);
            }

            /** @var DeliveryMonitoringData $data */
            $data = $deliveryMonitoringService->getData($deliveryExecution);

            if ($extendedTime) {
                /** @var TestSessionService $testSessionService */
                $testSessionService = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
                $inputParameters = $testSessionService->getRuntimeInputParameters($deliveryExecution);
                /** @var AssessmentTest $testDefinition */
                $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
                $deliveryExecutionArray[] = $deliveryExecution;
                $extraTime = null;
                $seconds = null;

                if ($testDefinition->getTimeLimits() && $testDefinition->getTimeLimits()->hasMaxTime()) {
                    $maxTime = $testDefinition->getTimeLimits()->getMaxTime();
                    $seconds = $maxTime->getSeconds(true);
                }

                /** @var TestSession $testSession */
                if ($testSession = $testSessionService->getTestSession($deliveryExecution)) {
                    $seconds = null;

                    if ($item = $testSession->getCurrentAssessmentItemRef()) {
                        if ($testSessionLimits = $item->getTimeLimits()) {
                            $seconds = $testSessionLimits->hasMaxTime()
                                ? $testSessionLimits->getMaxTime()->getSeconds(true)
                                : $seconds;
                        }
                    }

                    if (!$seconds && $section = $testSession->getCurrentAssessmentSection()) {
                        if ($testSessionLimits = $section->getTimeLimits()) {
                            $seconds = $testSessionLimits->hasMaxTime()
                                ? $testSessionLimits->getMaxTime()->getSeconds(true)
                                : $seconds;
                        }
                    }

                    if (!$seconds && $testPart = $testSession->getCurrentTestPart()) {
                        if ($testSessionLimits = $testPart->getTimeLimits()) {
                            $seconds = $testSessionLimits->hasMaxTime()
                                ? $testSessionLimits->getMaxTime()->getSeconds(true)
                                : $seconds;
                        }
                    }

                    if (!$seconds && $assessmentTest = $testSession->getAssessmentTest()) {
                        if ($testSessionLimits = $assessmentTest->getTimeLimits()) {
                            $seconds = $testSessionLimits->hasMaxTime()
                                ? $testSessionLimits->getMaxTime()->getSeconds(true)
                                : $seconds;
                        }
                    }
                }

                if ($seconds) {
                    $secondsNew = $seconds * $extendedTime;
                    $extraTime = $secondsNew - $seconds;

                    $dataArray = $data->get();
                    if (!isset($dataArray[DeliveryMonitoringService::REMAINING_TIME])) {
                        $data->update(DeliveryMonitoringService::REMAINING_TIME, $seconds);
                    }
                }
            }

            // reopen the execution if already closed
            if ($deliveryExecution->getState()->getUri() == DeliveryExecution::STATE_FINISHIED) {
                $deliveryExecution->setState(DeliveryExecution::STATE_ACTIVE);

                /** @var TestSessionService $testSessionService */
                $testSessionService = $this->getServiceManager()->get(TestSessionService::SERVICE_ID);

                /* @var TestSession $testSession */
                $testSession = $testSessionService->getTestSession($deliveryExecution);

                if ($testSession) {
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
                }
            }

            /** @var QtiTimer $timer */
            $timer = $this->getDeliveryTimer($deliveryExecution);
            $timer
                ->setExtraTime($extraTime)
                ->setExtendedTime($extendedTime)
                ->save();


            $data->update(DeliveryMonitoringService::EXTRA_TIME, $timer->getExtraTime());
            $data->update(DeliveryMonitoringService::EXTENDED_TIME, $extendedTime);
            $data->update(DeliveryMonitoringService::CONSUMED_EXTRA_TIME, $timer->getConsumedExtraTime());
            if ($deliveryMonitoringService->save($data)) {
                $result['processed'][$deliveryExecution->getIdentifier()] = true;
            } else {
                $result['unprocessed'][$deliveryExecution->getIdentifier()] = false;
            }
        }

        return $result;
    }

}

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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\implementation;

use common_session_SessionManager as SessionManager;
use Context;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\mutex\LockTrait;
use oat\oatbox\user\User;
use oat\taoDelivery\model\execution\AbstractStateService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\event\DeliveryLogEvent;
use oat\taoProctoring\model\event\DeliveryExecutionFinished;
use oat\taoProctoring\model\event\DeliveryExecutionIrregularityReport;
use oat\taoProctoring\model\event\DeliveryExecutionTerminated;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoQtiTest\models\ExtendedStateService;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use qtism\runtime\tests\AssessmentTestSessionState;
use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\Os;
use Symfony\Component\Lock\Lock;

/**
 * Class DeliveryExecutionStateService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class DeliveryExecutionStateService extends AbstractStateService implements
    \oat\taoProctoring\model\DeliveryExecutionStateService
{
    use LoggerAwareTrait;
    use LockTrait;

    public const OPTION_TERMINATION_DELAY_AFTER_PAUSE = 'termination_delay_after_pause';
    /**
     * @var string lifetime delivery executions in awaiting state
     */
    public const OPTION_CANCELLATION_DELAY = 'cancellation_delay';
    public const OPTION_TIME_HANDLING = 'time_handling';

    public const TIME_HANDLING_EXTRA_TIME = 'extra_time';
    public const TIME_HANDLING_TIMER_ADJUSTMENT = 'timer_adjustment';

    /**
     * @var TestSessionService
     */
    private $testSessionService;

    /** @var Lock[]  */
    private $executionLocks = [];

    /**
     * @return array
     */
    public function getDeliveriesStates()
    {
        return [
            ProctoredDeliveryExecution::STATE_FINISHED,
            ProctoredDeliveryExecution::STATE_ACTIVE,
            ProctoredDeliveryExecution::STATE_PAUSED,
            ProctoredDeliveryExecution::STATE_TERMINATED,
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\execution\AbstractStateService::getInitialStatus()
     */
    public function getInitialStatus($deliveryId, User $user)
    {
        $service = $this->getServiceLocator()->get(TestTakerAuthorizationService::SERVICE_ID);
        return $service->isProctored($deliveryId, $user)
            ? DeliveryExecution::STATE_PAUSED
            : DeliveryExecution::STATE_ACTIVE;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function waitExecution(DeliveryExecution $deliveryExecution)
    {
        $result = false;
        $this->lockExecution($deliveryExecution);
        $executionState = $deliveryExecution->getState()->getUri();
        if (
            ProctoredDeliveryExecution::STATE_TERMINATED !== $executionState &&
            ProctoredDeliveryExecution::STATE_FINISHED !== $executionState
        ) {
            $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_AWAITING);
            $this->getDeliveryLogService()->log($deliveryExecution->getIdentifier(), 'TEST_AWAITING_AUTHORISATION', [
                'timestamp' => microtime(true),
                'context' => $this->getContext($deliveryExecution),
            ]);
            $result = true;
        }

        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * Alias for self::run() (for backward capability).
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function resumeExecution(DeliveryExecution $deliveryExecution)
    {
        return $this->run($deliveryExecution);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function run(DeliveryExecution $deliveryExecution)
    {
        $this->lockExecution($deliveryExecution);
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        $logData = [
            'web_browser_name' => $this->getBrowserDetector()->getName(),
            'web_browser_version' => $this->getBrowserDetector()->getVersion(),
            'os_name' => $this->getOsDetector()->getName(),
            'os_version' => $this->getOsDetector()->getVersion(),
            'context' => $this->getContext($deliveryExecution),
        ];

        $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_ACTIVE);

        if ($session && $session->getState() !== AssessmentTestSessionState::INITIAL) {
            $session->resume();
            $this->getTestSessionService()->persist($session);
            $logData['timestamp'] = microtime(true);
            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_RESUME,
                $logData
            );
        } else {
            $logData['timestamp'] = microtime(true);
            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_RUN,
                $logData
            );
        }

        $this->releaseExecution($deliveryExecution);
        return true;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @param null $testCenter
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function authoriseExecution(DeliveryExecution $deliveryExecution, $reason = null, $testCenter = null)
    {
        $result = false;
        $this->lockExecution($deliveryExecution);
        if ($this->canBeAuthorised($deliveryExecution)) {
            $proctor = SessionManager::getSession()->getUser();
            $logData = [
                'proctorUri' => $proctor->getIdentifier(),
                'timestamp' => microtime(true),
            ];
            if (!empty($reason) && is_array($reason)) {
                $logData = array_merge($logData, $reason);
            }
            if ($testCenter !== null) {
                $logData['test_center'] = $testCenter;
            }
            $logData['itemId'] = $this->getCurrentItemId($deliveryExecution);
            $logData['context'] = $this->getContext($deliveryExecution);
            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_AUTHORISE,
                $logData
            );
            $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_AUTHORIZED);
            $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
            $eventManager->trigger(new AuthorizationGranted($deliveryExecution, $proctor));
            $result = true;
        }
        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \oat\taoDelivery\model\execution\StateServiceInterface::terminate()
     */
    public function terminate(DeliveryExecution $deliveryExecution)
    {
        $this->terminateExecution($deliveryExecution);
    }

    /**
     * Terminates a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \qtism\runtime\storage\common\StorageException
     * @throws \qtism\runtime\tests\AssessmentTestSessionException
     */
    public function terminateExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $this->lockExecution($deliveryExecution);
        $executionState = $deliveryExecution->getState()->getUri();
        $result = false;

        if (
            ProctoredDeliveryExecution::STATE_TERMINATED !== $executionState
            && ProctoredDeliveryExecution::STATE_FINISHED !== $executionState
        ) {
            $proctor = SessionManager::getSession()->getUser();
            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            $logData = [
                'reason' => $reason,
                'timestamp' => microtime(true),
                'context' => $this->getContext($deliveryExecution),
                'itemId' => $session ? $this->getCurrentItemId($deliveryExecution) : null,
            ];

            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_TERMINATE,
                $logData
            );

            if ($session) {
                if ($session->isRunning()) {
                    $session->endTestSession();
                }
                $this->getTestSessionService()->persist($session);
                $this->getServiceLocator()->get(ExtendedStateService::SERVICE_ID)->persist($session->getSessionId());
            }

            // Delivery execution state changes after test session ends, in the same way as it happens
            // when a human test taker takes the test.
            $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_TERMINATED);
            $eventManager->trigger(new DeliveryExecutionTerminated($deliveryExecution, $proctor, $reason));
            $result = true;
        }
        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * Alias for self::pause() (for backward capability).
     *
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \qtism\runtime\storage\common\StorageException
     */
    public function pauseExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        return $this->pause($deliveryExecution, $reason);
    }

    /**
     * Pauses a delivery execution
     *
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \qtism\runtime\storage\common\StorageException
     */
    public function pause(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $this->lockExecution($deliveryExecution);
        $executionState = $deliveryExecution->getState()->getUri();
        $result = false;

        if (
            ProctoredDeliveryExecution::STATE_TERMINATED !== $executionState
            && ProctoredDeliveryExecution::STATE_FINISHED !== $executionState
        ) {
            $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
            $data = [
                'reason' => $reason,
                'timestamp' => microtime(true),
                'context' => $this->getContext($deliveryExecution),
            ];
            $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_PAUSED);
            if ($session) {
                $data['itemId'] = $this->getCurrentItemId($deliveryExecution);
                if ($session->getState() !== AssessmentTestSessionState::SUSPENDED) {
                    $session->suspend();
                    $this->getTestSessionService()->persist($session);
                }
                $this->getServiceLocator()->get(ExtendedStateService::SERVICE_ID)->persist($session->getSessionId());
            }
            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_PAUSE,
                $data
            );
            $result = true;
        }
        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * Alias for self::finish() (for backward capability).
     *
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function finishExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        return $this->finish($deliveryExecution, $reason);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function finish(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $this->lockExecution($deliveryExecution);
        $result = $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_FINISHED, $reason);
        if ($result) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->trigger(new DeliveryExecutionFinished($deliveryExecution));
        }
        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param null $reason
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function cancelExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $this->lockExecution($deliveryExecution);
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        if ($session === null) {
            $data = [
                'reason' => $reason,
                'timestamp' => microtime(true),
                'context' => $this->getContext($deliveryExecution),
            ];
            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryLogEvent::EVENT_ID_TEST_CANCEL,
                $data
            );
            $result = $this->setState($deliveryExecution, ProctoredDeliveryExecution::STATE_CANCELED);
        } else {
            $this->logNotice(
                'Attempt to cancel delivery execution ' . $deliveryExecution->getIdentifier()
                    . ' with initialized test session.'
            );
            $result = false;
        }

        $this->releaseExecution($deliveryExecution);

        return $result;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function isCancelable(DeliveryExecution $deliveryExecution)
    {
        return $this->getTestSessionService()->getTestSession($deliveryExecution) === null;
    }

    /**
     * Report irregularity to a delivery execution
     *
     * @todo remove this method to separate service
     * @param DeliveryExecution $deliveryExecution
     * @param array $reason
     * @return bool
     */
    public function reportExecution(DeliveryExecution $deliveryExecution, $reason)
    {
        $deliveryLog = $this->getDeliveryLogService();
        $data = [
            'reason' => $reason,
            'timestamp' => microtime(true),
            'itemId' => $this->getCurrentItemId($deliveryExecution),
            'context' => $this->getContext($deliveryExecution)
        ];
        $returnValue = $deliveryLog->log(
            $deliveryExecution->getIdentifier(),
            DeliveryLogEvent::EVENT_ID_TEST_IRREGULARITY,
            $data
        );

        // Trigger a report event.
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new DeliveryExecutionIrregularityReport($deliveryExecution));

        return $returnValue;
    }

    /**
     * @inheritdoc
     */
    public function legacyTransition(DeliveryExecution $deliveryExecution, $state)
    {
        $reason = null;
        $testCenter = null;
        switch ($state) {
            case ProctoredDeliveryExecution::STATE_ACTIVE:
                $result = $this->resumeExecution($deliveryExecution);
                break;
            case ProctoredDeliveryExecution::STATE_AUTHORIZED:
                $result = $this->authoriseExecution($deliveryExecution, $reason, $testCenter);
                break;
            case ProctoredDeliveryExecution::STATE_AWAITING:
                $result = $this->waitExecution($deliveryExecution);
                break;
            case ProctoredDeliveryExecution::STATE_CANCELED:
                $result = $this->cancelExecution($deliveryExecution, $reason);
                break;
            case ProctoredDeliveryExecution::STATE_FINISHED:
                $result = $this->finishExecution($deliveryExecution, $reason);
                break;
            case ProctoredDeliveryExecution::STATE_PAUSED:
                $result = $this->pauseExecution($deliveryExecution, $reason);
                break;
            case ProctoredDeliveryExecution::STATE_TERMINATED:
                $result = $this->terminateExecution($deliveryExecution, $reason);
                break;
            default:
                $this->logWarning('Unrecognised state ' . $state);
                $result = $this->setState($deliveryExecution, $state);
        }

        return $result;
    }

    /**
     * Whether delivery execution can be moved to authorised state.
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    protected function canBeAuthorised(DeliveryExecution $deliveryExecution)
    {
        $result = false;
        $user = SessionManager::getSession()->getUser();
        $stateUri = $deliveryExecution->getState()->getUri();
        if ($stateUri === ProctoredDeliveryExecution::STATE_AWAITING) {
            $result = true;
        }

        if (
            $user instanceof GuestTestUser &&
            !in_array($stateUri, [
                ProctoredDeliveryExecution::STATE_FINISHED,
                ProctoredDeliveryExecution::STATE_TERMINATED,
                ProctoredDeliveryExecution::STATE_CANCELED,
            ])
        ) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return DeliveryLog
     */
    private function getDeliveryLogService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * Gets test session service
     *
     * @return TestSessionService
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    private function getTestSessionService()
    {
        if ($this->testSessionService === null) {
            $this->testSessionService = $this->getServiceManager()->get(TestSessionService::SERVICE_ID);
        }
        return $this->testSessionService;
    }

    /**
     * Get identifier of current item.
     * @param DeliveryExecution $deliveryExecution
     * @return null|string
     */
    protected function getCurrentItemId(DeliveryExecution $deliveryExecution)
    {
        $result = null;
        $session = $this->getTestSessionService()->getTestSession($deliveryExecution);
        if ($session) {
            $item = $session->getCurrentAssessmentItemRef();
            if ($item) {
                $result = $item->getIdentifier();
            }
        }
        return $result;
    }

    /**
     * Pause delivery execution if test session was paused.
     * @param TestExecutionPausedEvent $event
     */
    public function catchSessionPause(TestExecutionPausedEvent $event)
    {
        $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($event->getTestExecutionId());
        /** @var DeliveryExecutionStateService $service */
        $requestParams = Context::getInstance()->getRequest()->getParameters();
        $reason = null;
        if (isset($requestParams['reason'])) {
            $reason = $requestParams['reason'];
        }
        if ($deliveryExecution->getState()->getUri() !== DeliveryExecution::STATE_PAUSED) {
            $this->pause($deliveryExecution, $reason);
        }
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return string
     */
    protected function getContext(DeliveryExecution $deliveryExecution)
    {
        $result = 'cli' === php_sapi_name()
            ? $_SERVER['PHP_SELF']
            : Context::getInstance()->getRequest()->getRequestURI();

        return $result;
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param null|string $reason
     * @return bool
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function reactivateExecution(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $this->lockExecution($deliveryExecution);
        $executionState = $deliveryExecution->getState()->getUri();

        $result = parent::reactivateExecution($deliveryExecution, $reason);

        if (ProctoredDeliveryExecution::STATE_TERMINATED === $executionState) {
            $logData = [
                'reason' => $reason,
                'timestamp' => microtime(true),
                'context' => $this->getContext($deliveryExecution),
            ];

            $this->getDeliveryLogService()->log(
                $deliveryExecution->getIdentifier(),
                DeliveryExecutionReactivated::LOG_KEY,
                $logData
            );
        }
        $this->releaseExecution($deliveryExecution);
        return $result;
    }

    /**
     * Get the browser detector
     *
     * @return Browser
     */
    protected function getBrowserDetector()
    {
        return new Browser();
    }

    /**
     * Get the operating system detector
     *
     * @return Os
     */
    protected function getOsDetector()
    {
        return new Os();
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     */
    protected function lockExecution(DeliveryExecution $deliveryExecution)
    {
        $deId = $deliveryExecution->getIdentifier();
        $this->executionLocks[$deId] = $this->createLock(static::class . $deId, 30);
        $this->executionLocks[$deId]->acquire(true);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     */
    protected function releaseExecution(DeliveryExecution $deliveryExecution)
    {
        $deId = $deliveryExecution->getIdentifier();
        if (isset($this->executionLocks[$deId])) {
            $this->executionLocks[$deId]->release();
            unset($this->executionLocks[$deId]);
        }
    }
}

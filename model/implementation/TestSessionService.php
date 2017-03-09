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

use DateInterval;
use DateTimeImmutable;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoQtiTest\models\runner\session\UserUriAware;
use qtism\runtime\storage\binary\BinaryAssessmentTestSeeker;
use qtism\runtime\tests\AssessmentTestSession;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionState;
use \oat\oatbox\service\ConfigurableService;

/**
 * Interface TestSessionService
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TestSessionService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/TestSessionService';

    /** @var array cache to store session instances */
    protected $cache = [];

    public static function singleton()
    {
        return ServiceManager::getServiceManager()->get(TestSessionService::SERVICE_ID);
    }

    /**
     * Gets the test session for a particular deliveryExecution
     *
     * @param DeliveryExecution $deliveryExecution
     * @return \qtism\runtime\tests\AssessmentTestSession
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function getTestSession(DeliveryExecution $deliveryExecution)
    {
        if (!isset($this->cache[$deliveryExecution->getIdentifier()]['session'])) {
            $resultServer = \taoResultServer_models_classes_ResultServerStateFull::singleton();

            $compiledDelivery = $deliveryExecution->getDelivery();
            $inputParameters = $this->getRuntimeInputParameters($deliveryExecution);

            $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
            $testResource = new \core_kernel_classes_Resource($inputParameters['QtiTestDefinition']);

            $sessionManager = new \taoQtiTest_helpers_SessionManager($resultServer, $testResource);

            $userId = $deliveryExecution->getUserIdentifier();
            $qtiStorage = new \taoQtiTest_helpers_TestSessionStorage(
                $sessionManager,
                new BinaryAssessmentTestSeeker($testDefinition), $userId
            );

            $sessionId = $deliveryExecution->getIdentifier();

            if ($qtiStorage->exists($sessionId)) {
                $session = $qtiStorage->retrieve($testDefinition, $sessionId);
                if ($session instanceof UserUriAware) {
                    $session->setUserUri($userId);
                }

                $resultServerUri = $compiledDelivery->getOnePropertyValue(new \core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
                $resultServerObject = new \taoResultServer_models_classes_ResultServer($resultServerUri, array());
                $resultServer->setValue('resultServerUri', $resultServerUri->getUri());
                $resultServer->setValue('resultServerObject', array($resultServerUri->getUri() => $resultServerObject));
                $resultServer->setValue('resultServer_deliveryResultIdentifier', $deliveryExecution->getIdentifier());
            } else {
                $session = null;
            }

            $this->cache[$deliveryExecution->getIdentifier()] = [
                'session' => $session,
                'storage' => $qtiStorage
            ];
        }

        return $this->cache[$deliveryExecution->getIdentifier()]['session'];
    }

    /**
     *
     * @param DeliveryExecution $deliveryExecution
     * @return array
     * Example:
     * <pre>
     * array(
     *   'QtiTestCompilation' => 'http://sample/first.rdf#i14369768868163155-|http://sample/first.rdf#i1436976886612156+',
     *   'QtiTestDefinition' => 'http://sample/first.rdf#i14369752345581135'
     * )
     * </pre>
     */
    public function getRuntimeInputParameters(DeliveryExecution $deliveryExecution)
    {
        $compiledDelivery = $deliveryExecution->getDelivery();
        $runtime = $this->getServiceLocator()->get(AssignmentService::CONFIG_ID)->getRuntime($compiledDelivery->getUri());
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters;
    }

    /**
     * Checks if delivery execution was expired after pausing or abandoned after authorization
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     */
    public function isExpired(DeliveryExecution $deliveryExecution)
    {
        if (!isset($this->cache[$deliveryExecution->getIdentifier()]['expired'])) {
            $executionState = $deliveryExecution->getState()->getUri();
            if (!in_array($executionState, [
                DeliveryExecutionState::STATE_PAUSED,
                DeliveryExecutionState::STATE_ACTIVE,
                DeliveryExecutionState::STATE_AWAITING,
                DeliveryExecutionState::STATE_AUTHORIZED,
            ]) ||
                !$lastTestTakersEvent = $this->getLastTestTakersEvent($deliveryExecution)) {
                return $this->cache[$deliveryExecution->getIdentifier()]['expired'] = false;
            }

            /** @var \oat\taoProctoring\model\implementation\DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);

            if (($executionState === DeliveryExecutionState::STATE_AUTHORIZED ||
                  $executionState === DeliveryExecutionState::STATE_AWAITING) &&
                $deliveryExecutionStateService->isCancelable($deliveryExecution)) {
                $delay = $deliveryExecutionStateService->getOption(DeliveryExecutionStateService::OPTION_CANCELLATION_DELAY);
                $startedTimestamp = \tao_helpers_Date::getTimeStamp($deliveryExecution->getStartTime(), true);
                $started = (new DateTimeImmutable())->setTimestamp($startedTimestamp);
                if ($started->add(new DateInterval($delay)) < (new DateTimeImmutable())) {
                    $this->cache[$deliveryExecution->getIdentifier()]['expired'] = true;
                    return $this->cache[$deliveryExecution->getIdentifier()]['expired'];
                }
            }

            $wasPausedAt = (new DateTimeImmutable())->setTimestamp($lastTestTakersEvent['created_at']);
            if ($wasPausedAt && $deliveryExecutionStateService->hasOption(DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE)) {
                $delay = $deliveryExecutionStateService->getOption(DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE);
                if ($wasPausedAt->add(new DateInterval($delay)) < (new DateTimeImmutable())) {
                    $this->cache[$deliveryExecution->getIdentifier()]['expired'] = true;

                    return $this->cache[$deliveryExecution->getIdentifier()]['expired'];
                }
            }

            $this->cache[$deliveryExecution->getIdentifier()]['expired'] = false;
        }

        return $this->cache[$deliveryExecution->getIdentifier()]['expired'];
    }

    /**
     * @param AssessmentTestSession $session
     */
    public function persist(AssessmentTestSession $session)
    {
        $sessionId = $session->getSessionId();
        $storage = $this->cache[$sessionId]['storage'];
        $storage->persist($session);
    }

    /**
     * Get last test takers event from delivery log
     * @param DeliveryExecution $deliveryExecution
     * @return array|null
     */
    protected function getLastTestTakersEvent(DeliveryExecution $deliveryExecution)
    {
        $deliveryLogService = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $testTakerIdentifier = $deliveryExecution->getUserIdentifier();
        $events = array_reverse($deliveryLogService->get($deliveryExecution->getIdentifier()));

        $lastTestTakersEvent = null;
        foreach ($events as $event) {
            if ($event[DeliveryLog::CREATED_BY] === $testTakerIdentifier) {
                $lastTestTakersEvent = $event;
                break;
            }
        }

        return $lastTestTakersEvent;
    }

}

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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\Command;

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

class ProctorCommandManagerService extends ConfigurableService  implements ProctorCommandManagerInterface
{
    use ServiceManagerAwareTrait;

    const OPTION_SHOULD_EXECUTE_LATER = 'should_execute_later';

    /** @var ProctorCommandStorageInterface */
    private $commandStorage;

    /** @var DeliveryExecutionStateService */
    private $deliveryExecutionStateService;

    /**
     * @inheritdoc
     */
    public function pause(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), ProctoredDeliveryExecution::STATE_PAUSED);
        $proctorCommand->setDeliveryExecution($deliveryExecution);
        $proctorCommand->setReason($reason);

        return $this->next($proctorCommand);
    }

    /**
     * @inheritdoc
     */
    public function authorise(DeliveryExecution $deliveryExecution, $testCenter, $reason = null)
    {
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), ProctoredDeliveryExecution::STATE_AUTHORIZED);
        $proctorCommand->setDeliveryExecution($deliveryExecution);
        $proctorCommand->setReason($reason);
        $proctorCommand->setTestCenter($testCenter);

        return $this->next($proctorCommand);
    }

    /**
     * @inheritdoc
     */
    public function terminate(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), ProctoredDeliveryExecution::STATE_TERMINATED);
        $proctorCommand->setDeliveryExecution($deliveryExecution);
        $proctorCommand->setReason($reason);

        return $this->next($proctorCommand);
    }

    /**
     * @inheritdoc
     */
    public function reactivate(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), ProctoredDeliveryExecution::STATE_REACTIVATED);
        $proctorCommand->setDeliveryExecution($deliveryExecution);
        $proctorCommand->setReason($reason);

        return $this->next($proctorCommand);
    }

    /**
     * @inheritdoc
     */
    public function report(DeliveryExecution $deliveryExecution, $reason = null)
    {
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), $deliveryExecution->getState()->getUri());
        $proctorCommand->setDeliveryExecution($deliveryExecution);
        $proctorCommand->setReason($reason);

        return $this->next($proctorCommand);
    }

    /**
     * @inheritdoc
     */
    public function executeCommand(ProctorCommand $proctorCommand)
    {
        switch ($proctorCommand->getFutureState()){
            case ProctoredDeliveryExecution::STATE_AUTHORIZED:
                return $this->getDeliveryExecutionStateService()->authoriseExecution(
                    $proctorCommand->getDeliveryExecution(),
                    $proctorCommand->getReason(),
                    $proctorCommand->getTestCenter()
                );

            case ProctoredDeliveryExecution::STATE_TERMINATED:
                return $this->getDeliveryExecutionStateService()->terminateExecution(
                    $proctorCommand->getDeliveryExecution(),
                    $proctorCommand->getReason()
                );

            case ProctoredDeliveryExecution::STATE_REACTIVATED:
                return $this->getDeliveryExecutionStateService()->reactivateExecution(
                    $proctorCommand->getDeliveryExecution(),
                    $proctorCommand->getReason()
                );

            case ProctoredDeliveryExecution::STATE_PAUSED:
                return $this->getDeliveryExecutionStateService()->pauseExecution(
                    $proctorCommand->getDeliveryExecution(),
                    $proctorCommand->getReason()
                );

            case ProctoredDeliveryExecution::STATE_ACTIVE:
                return $this->getDeliveryExecutionStateService()->reportExecution(
                    $proctorCommand->getDeliveryExecution(),
                    $proctorCommand->getReason()
                );
        }
    }

    /**
     * @inheritdoc
     */
    public function executeByDeliveryExecution($deliveryExecutionId)
    {
        $proctorCommand = $this->getCommandStorage()->get($deliveryExecutionId);
        if (!is_null($proctorCommand)){
            return $this->executeCommand($proctorCommand);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function shouldExecuteLaterEnable()
    {
        return (bool) $this->getOption(static::OPTION_SHOULD_EXECUTE_LATER);
    }

    /**
     * @return ProctorCommandStorageInterface
     */
    protected function getCommandStorage()
    {
        if (is_null($this->commandStorage)){
            /** @var ProctorCommandStorageInterface $service */
            $service = $this->getServiceLocator()->get(ProctorCommandStorageInterface::SERVICE_ID);
            $this->commandStorage = $service;
        }

        return $this->commandStorage;
    }

    /**
     * @return DeliveryExecutionStateService
     */
    protected function getDeliveryExecutionStateService()
    {
        if (is_null($this->deliveryExecutionStateService)){
            /** @var DeliveryExecutionStateService $service */
            $service = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
            $this->deliveryExecutionStateService = $service;
        }

        return $this->deliveryExecutionStateService;
    }

    /**
     * @param ProctorCommand $proctorCommand
     * @return bool|mixed
     * @throws \Exception
     */
    protected function next(ProctorCommand $proctorCommand)
    {
        if ($this->shouldExecuteLaterEnable()){
            return $this->getCommandStorage()->put($proctorCommand);
        }

        return $this->executeCommand($proctorCommand);
    }

}
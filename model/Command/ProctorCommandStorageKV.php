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
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;

class ProctorCommandStorageKV extends ConfigurableService implements ProctorCommandStorageInterface
{
    use ServiceManagerAwareTrait;

    const OPTION_PERSISTENCE_ID = 'storage';
    const PREFIX = 'proctor::commands';

    /** @var \common_persistence_AdvKvDriver */
    private $persistence;

    /** @var DeliveryExecutionManagerService */
    private $deliveryExecutionManager;

    /**
     * @inheritdoc
     */
    public function put(ProctorCommand $proctorCommand)
    {
        return $this->getPersistence()->set(static::PREFIX. $proctorCommand->getDeliveryExecutionId(), json_encode($proctorCommand));
    }

    /**
     * @inheritdoc
     */
    public function get($deliveryExecutionId)
    {
        $data = json_decode($this->getPersistence()->get(static::PREFIX.$deliveryExecutionId), true);
        if (is_null($data)){
            return null;
        }

        $deliveryExecution = $this->getDeliveryExecutionManager()->getDeliveryExecutionById($data['deliveryExecutionId']);
        $proctorCommand = new ProctorCommand($deliveryExecution->getIdentifier(), $data['futureState']);
        $proctorCommand->setDeliveryExecution($deliveryExecution);

        if (isset($data['reason'])){
            $proctorCommand->setReason(json_decode($data['reason'], true));
        }

        if (isset($data['testCenter'])){
            $proctorCommand->setTestCenter($data['testCenter']);
        }

        $this->delete($deliveryExecutionId);

        return $proctorCommand;
    }

    /**
     * @inheritdoc
     */
    public function delete($deliveryExecutionId)
    {
        return $this->getPersistence()->del(static::PREFIX.$deliveryExecutionId);
    }

    /**
     * @return \common_persistence_AdvKvDriver|\common_persistence_Persistence
     */
    protected function getPersistence()
    {
        if (is_null($this->persistence)){
            $persistence = \common_persistence_KeyValuePersistence::getPersistence($this->getOption(static::OPTION_PERSISTENCE_ID));
            $this->persistence = $persistence;
        }

        return $this->persistence;
    }

    /**
     * @return DeliveryExecutionManagerService
     */
    protected function getDeliveryExecutionManager()
    {
        if (is_null($this->deliveryExecutionManager)){
            $service = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);

            $this->deliveryExecutionManager = $service;
        }

        return $this->deliveryExecutionManager;
    }
}
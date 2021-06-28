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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\model\listener;

use common_exception_Error;
use common_exception_NotFound;
use common_session_SessionManager;
use Exception;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\tao\model\event\MetadataModified;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringData;
use oat\taoProctoring\model\repository\MonitoringRepository;
use oat\taoProctoring\model\Tasks\DeliveryUpdaterTask;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use tao_helpers_Date;

class MonitoringListener extends ConfigurableService
{
    /**
     * @param DeliveryExecutionCreated $event
     * @throws common_exception_NotFound
     * @throws Exception
     */
    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();

        $data = $this->getMonitoringRepository()->createMonitoringData($deliveryExecution, []);

        $data = $this->updateDeliveryInformation($data, $deliveryExecution);
        $data = $this->updateTestTakerInformation($data, $event->getUser());

        $data->updateData([DeliveryMonitoringService::CONNECTIVITY]);
        $success = $this->getMonitoringRepository()->save($data);
        if (!$success) {
            $this->logWarning('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    }

    /**
     * @param DeliveryExecutionState $event
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws Exception
     */
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $data = $this->getMonitoringRepository()->createMonitoringData($event->getDeliveryExecution());

        $this->fillMonitoringOnExecutionStateChanged($event, $data);

        $success = $this->getMonitoringRepository()->partialSave($data);
        if (!$success) {
            $this->logWarning('monitor cache for delivery ' . $event->getDeliveryExecution()->getIdentifier() . ' could not be created');
        }
    }

    /**
     * @param DeliveryExecutionState $event
     * @param DeliveryMonitoringData $data
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    protected function fillMonitoringOnExecutionStateChanged(DeliveryExecutionState $event, DeliveryMonitoringData $data)
    {
        $data->update(DeliveryMonitoringService::STATUS, $event->getState());
        $data->updateData([DeliveryMonitoringService::CONNECTIVITY]);
        $user = common_session_SessionManager::getSession()->getUser();

        if (in_array($event->getState(), [DeliveryExecution::STATE_AWAITING, DeliveryExecution::STATE_PAUSED])
            && $user instanceof GuestTestUser) {
            $data->getDeliveryExecution()->setState(DeliveryExecution::STATE_AUTHORIZED);
        }

        if ($event->getState() == DeliveryExecution::STATE_TERMINATED) {
            $data->update(
                DeliveryMonitoringService::END_TIME,
                tao_helpers_Date::getTimeStamp(time(), true)
            );
        }
        if ($event->getState() == DeliveryExecution::STATE_FINISHED) {
            $data->update(
                DeliveryMonitoringService::END_TIME,
                tao_helpers_Date::getTimeStamp($event->getDeliveryExecution()->getFinishTime(), true)
            );
        }
    }

    /**
     * Something changed in the state of the test execution
     * (for example: the current item in the test)
     *
     * @param TestChangedEvent $event
     * @throws common_exception_NotFound
     * @throws common_exception_Error
     * @throws Exception
     */
    public function testStateChanged(TestChangedEvent $event)
    {
        $deliveryExecution = $this->getServiceLocator()->get(DeliveryExecutionService::SERVICE_ID)
            ->getDeliveryExecution($event->getServiceCallId());

        $data = $this->getMonitoringRepository()->createMonitoringData($deliveryExecution);

        $data->update(DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM, $event->getNewStateDescription());
        if ($event instanceof QtiTestChangeEvent) {
            $data->setTestSession($event->getSession());
            $data->updateData([
                DeliveryMonitoringService::REMAINING_TIME,
                DeliveryMonitoringService::EXTRA_TIME,
                DeliveryMonitoringService::CONNECTIVITY
            ]);
        }

        $dataKeys = [
            DeliveryMonitoringService::STATUS,
        ];
        $session = $event->getSession();
        $userId = common_session_SessionManager::getSession()->getUser()->getIdentifier();
        if ($deliveryExecution->getUserIdentifier() === $userId) {
            $dataKeys[] = DeliveryMonitoringService::DIFF_TIMESTAMP;
            $dataKeys[] = DeliveryMonitoringService::LAST_TEST_TAKER_ACTIVITY;
        }
        $data->setTestSession($session);
        $data->updateData($dataKeys);

        $success = $this->getMonitoringRepository()->partialSave($data);
        if (!$success) {
            $this->logWarning('monitor cache for teststate could not be updated');
        }
    }

    /**
     * The status of the test execution has changed
     * (for example: from running to paused)
     *
     * @param QtiTestStateChangeEvent $event
     * @throws common_exception_NotFound
     * @throws Exception
     */
    public function qtiTestStatusChanged(QtiTestStateChangeEvent $event)
    {
        /** @var DeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceLocator()->get(DeliveryExecutionService::SERVICE_ID);
        $deliveryExecution = $deliveryExecutionService->getDeliveryExecution($event->getServiceCallId());

        $data = $this->getMonitoringRepository()->createMonitoringData($deliveryExecution);

        $data->setTestSession($event->getSession());
        $data->update(DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM, $event->getNewStateDescription());
        $data->updateData([
            DeliveryMonitoringService::CONNECTIVITY,
            DeliveryMonitoringService::REMAINING_TIME
        ]);
        $success = $this->getMonitoringRepository()->partialSave($data);
        if (!$success) {
            $this->logWarning('monitor cache for teststate could not be updated');
        }
    }

    /**
     * Update the label of the delivery across the entrie cache
     *
     * @param MetadataModified $event
     */
    public function deliveryLabelChanged(MetadataModified $event)
    {
        $resource = $event->getResource();
        if ($event->getMetadataUri() === OntologyRdfs::RDFS_LABEL) {
            $assemblyClass = DeliveryAssemblyService::singleton()->getRootClass();
            if ($resource->isInstanceOf($assemblyClass)) {
                /** @var $queueService QueueDispatcherInterface */
                $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
                $queueService->createTask(
                    new DeliveryUpdaterTask(),
                    [$resource->getUri(), $event->getMetadataValue()],
                    'Update delivery label'
                );
            }
        }
    }

    /**
     * Sets the protor who authorized this delivery execution
     *
     * @param AuthorizationGranted $event
     * @throws common_exception_NotFound
     * @throws Exception
     */
    public function deliveryAuthorized(AuthorizationGranted $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();
        $data = $this->getMonitoringRepository()->createMonitoringData($deliveryExecution);

        $data->update(DeliveryMonitoringService::AUTHORIZED_BY, $event->getAuthorizer()->getIdentifier());
        if (!$this->getMonitoringRepository()->partialSave($data)) {
            $this->logWarning('monitor cache for authorization could not be updated');
        }
    }

    /**
     * @param DeliveryExecutionReactivated $event
     * @throws common_exception_NotFound
     * @throws Exception
     */
    public function catchTestReactivatedEvent(DeliveryExecutionReactivated $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();

        $data = $this->getMonitoringRepository()->createMonitoringData($deliveryExecution);

        $data->update(DeliveryMonitoringService::REACTIVATE_AUTHORIZED_BY, $event->getUser()->getIdentifier());

        $success = $this->getMonitoringRepository()->partialSave($data);
        if (!$success) {
            $this->logWarning('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    }

    /**
     * @param DeliveryMonitoringData $data
     * @param User $user
     * @return DeliveryMonitoringData
     */
    private function updateTestTakerInformation($data, $user)
    {
        // need to add user to event
        $firstNames = $user->getPropertyValues(GenerisRdf::PROPERTY_USER_FIRSTNAME);
        if (!empty($firstNames)) {
            $data->update(DeliveryMonitoringService::TEST_TAKER_FIRST_NAME, reset($firstNames));
        }
        $lastNames = $user->getPropertyValues(GenerisRdf::PROPERTY_USER_LASTNAME);
        if (!empty($lastNames)) {
            $data->update(DeliveryMonitoringService::TEST_TAKER_LAST_NAME, reset($lastNames));
        }

        return $data;
    }

    /**
     * @param DeliveryMonitoringData $data
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return DeliveryMonitoringData
     * @throws common_exception_NotFound
     */
    private function updateDeliveryInformation($data, $deliveryExecution)
    {
        $data->update(DeliveryMonitoringService::STATUS, $deliveryExecution->getState()->getUri());
        $data->update(DeliveryMonitoringService::TEST_TAKER, $deliveryExecution->getUserIdentifier());
        $data->update(DeliveryMonitoringService::DELIVERY_ID, $deliveryExecution->getDelivery()->getUri());
        $data->update(DeliveryMonitoringService::DELIVERY_NAME, $deliveryExecution->getDelivery()->getLabel());
        $data->update(
            DeliveryMonitoringService::START_TIME,
            tao_helpers_Date::getTimeStamp($deliveryExecution->getStartTime(), true)
        );

        return $data;
    }

    private function getMonitoringRepository(): MonitoringRepository
    {
        return $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
    }
}

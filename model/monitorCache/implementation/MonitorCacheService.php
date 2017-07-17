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

use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\tao\model\event\MetadataModified;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;
use oat\taoProctoring\model\monitorCache\update\DeliveryUpdater;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoProctoring\model\authorization\AuthorizationGranted;

/**
 * Class MonitorCacheService
 *
 * RDS Implementation of DeliveryMonitoringService without using autoincrement fields and PDO::lastInsertId function.
 *
 * @package oat\taoProctoring\model
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class MonitorCacheService extends MonitoringStorage
{

    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();

        $data = $this->getData($deliveryExecution);
        $data->update(DeliveryMonitoringService::STATUS, $deliveryExecution->getState()->getUri());
        $data->update(DeliveryMonitoringService::TEST_TAKER, $deliveryExecution->getUserIdentifier());
        // need to add user to event
        $firstNames = $event->getUser()->getPropertyValues(PROPERTY_USER_FIRSTNAME);
        if (!empty($firstNames)) {
            $data->update(DeliveryMonitoringService::TEST_TAKER_FIRST_NAME, reset($firstNames));
        }
        $lastNames = $event->getUser()->getPropertyValues(PROPERTY_USER_LASTNAME);
        if (!empty($lastNames)) {
            $data->update(DeliveryMonitoringService::TEST_TAKER_LAST_NAME, reset($lastNames));
        }

        $data->update(DeliveryMonitoringService::DELIVERY_ID, $deliveryExecution->getDelivery()->getUri());
        $data->update(DeliveryMonitoringService::DELIVERY_NAME, $deliveryExecution->getDelivery()->getLabel());
        $data->update(
            DeliveryMonitoringService::START_TIME,
            \tao_helpers_Date::getTimeStamp($deliveryExecution->getStartTime(), true)
        );
        $data->updateData([DeliveryMonitoringService::CONNECTIVITY]);
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $deliveryExecution->getIdentifier() . ' could not be created');
        }
    }
    
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $deliveryExecution = $event->getDeliveryExecution();
        $data = $this->getData($deliveryExecution);
        $data->update(DeliveryMonitoringService::STATUS, $event->getState());
        $data->updateData([DeliveryMonitoringService::CONNECTIVITY]);
        $user = \common_session_SessionManager::getSession()->getUser();

        if (in_array($event->getState(), [DeliveryExecution::STATE_AWAITING, DeliveryExecution::STATE_PAUSED])
            && $user instanceof GuestTestUser) {
            $deliveryExecution->setState(DeliveryExecution::STATE_AUTHORIZED);

        }

        if ($event->getState() == DeliveryExecution::STATE_FINISHIED) {
            $data->update(
                DeliveryMonitoringService::END_TIME,
                \tao_helpers_Date::getTimeStamp($event->getDeliveryExecution()->getFinishTime(), true)
            );
        }
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for delivery ' . $event->getDeliveryExecution()->getIdentifier() . ' could not be created');
        }
    }

    /**
     * Something changed in the state of the test execution
     * (for example: the current item in the test)
     *
     * @param TestChangedEvent $event
     */
    public function testStateChanged(TestChangedEvent $event)
    {
        $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
        $data = $this->getData($deliveryExecution);
        $data->update(DeliveryMonitoringService::CURRENT_ASSESSMENT_ITEM, $event->getNewStateDescription());
        if ($event instanceof QtiTestChangeEvent) {
            $data->setTestSession($event->getSession());
            $data->updateData([
                DeliveryMonitoringService::REMAINING_TIME,
                DeliveryMonitoringService::EXTRA_TIME,
                DeliveryMonitoringService::CONNECTIVITY
            ]);
        }
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }

    /**
     * The status of the test execution has changed
     * (for example: from running to paused)
     *
     * @param QtiTestStateChangeEvent $event
     */
    public function qtiTestStatusChanged(QtiTestStateChangeEvent $event)
    {
        // assumes test execution id = delivery execution id
        $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($event->getServiceCallId());
        $data = $this->getData($deliveryExecution);
        $data->setTestSession($event->getSession());
        $data->updateData([
            DeliveryMonitoringService::CONNECTIVITY
        ]);
        $success = $this->save($data);
        if (!$success) {
            \common_Logger::w('monitor cache for teststate could not be updated');
        }
    }

    /**
     * Update the label of the delivery across the entrie cache
     *
     * @param MetadataModified $event
     */
    public function deliverylabelChanged(MetadataModified $event)
    {
        $resource = $event->getResource();
        if ($event->getMetadataUri() === RDFS_LABEL) {
            $assemblyClass = DeliveryAssemblyService::singleton()->getRootClass();
            if ($resource->isInstanceOf($assemblyClass)) {
                $update = new DeliveryUpdater();
                $update->changeLabel($this, $resource->getUri(), $event->getMetadataValue());
            }
        }
    }

    /**
     * Sets the protor who authorized this delivery execution
     * @param AuthorizationGranted $event
     */
    public function deliveryAuthorized(AuthorizationGranted $event)
    {
        $data = $this->getData($event->getDeliveryExecution());
        $data->update(DeliveryMonitoringService::AUTHORIZED_BY, $event->getAuthorizer()->getIdentifier());
        if (!$this->save($data)) {
            \common_Logger::w('monitor cache for authorization could not be updated');
        }
    }
}

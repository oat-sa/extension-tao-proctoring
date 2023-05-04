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

namespace oat\taoProctoring\scripts\tools;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\event\MetadataModified;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\listener\MonitoringListener;
use oat\taoProctoring\model\listener\MonitoringListenerInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\repository\MonitoringRepository;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class MonitoringExtraFieldConfigurationMigration extends ScriptAction
{
    protected function provideOptions()
    {
        return [];
    }

    protected function provideDescription()
    {
        return 'Migrate services and events to enable storage improvement for proctoring monitoring.';
    }

    protected function run()
    {
        $this->migrateEvents();
        ;

        $this->registerMonitoringRepository();

        $this->registerMonitoringListener();

        $report = new Report(Report::TYPE_SUCCESS, 'Events and services are now configured to use new data schema');
        $report->add(
            new Report(
                Report::TYPE_INFO,
                'If you have delivery executions, you should migrate them via: ' . PHP_EOL .
                '`php index.php \'\oat\taoProctoring\scripts\tools\MonitoringExtraFieldMigration\' -h`' . PHP_EOL .
                'This script can take time depending of number of delivery execution to migrate'
            )
        );

        return $report;
    }

    private function migrateEvents(): void
    {
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        $this->detachLegacyEvents($eventManager);
        $this->attachNewEvents($eventManager);

        $this->registerService(EventManager::SERVICE_ID, $eventManager);
    }

    private function detachLegacyEvents(EventManager $eventManager): void
    {
        $eventManager->detach(
            DeliveryExecutionState::class,
            [DeliveryMonitoringService::SERVICE_ID, 'executionStateChanged']
        );
        $eventManager->detach(
            DeliveryExecutionCreated::class,
            [DeliveryMonitoringService::SERVICE_ID, 'executionCreated']
        );
        $eventManager->detach(
            MetadataModified::class,
            [DeliveryMonitoringService::SERVICE_ID, 'deliveryLabelChanged']
        );
        $eventManager->detach(
            TestChangedEvent::EVENT_NAME,
            [DeliveryMonitoringService::SERVICE_ID, 'testStateChanged']
        );
        $eventManager->detach(
            QtiTestStateChangeEvent::EVENT_NAME,
            [DeliveryMonitoringService::SERVICE_ID, 'qtiTestStatusChanged']
        );
        $eventManager->detach(
            AuthorizationGranted::EVENT_NAME,
            [DeliveryMonitoringService::SERVICE_ID, 'deliveryAuthorized']
        );
    }

    private function attachNewEvents(EventManager $eventManager): void
    {
        $eventManager->attach(
            DeliveryExecutionState::class,
            [MonitoringListenerInterface::SERVICE_ID, 'executionStateChanged']
        );
        $eventManager->attach(
            DeliveryExecutionCreated::class,
            [MonitoringListenerInterface::SERVICE_ID, 'executionCreated']
        );
        $eventManager->attach(
            MetadataModified::class,
            [MonitoringListenerInterface::SERVICE_ID, 'deliveryLabelChanged']
        );
        $eventManager->attach(
            TestChangedEvent::EVENT_NAME,
            [MonitoringListenerInterface::SERVICE_ID, 'testStateChanged']
        );
        $eventManager->attach(
            QtiTestStateChangeEvent::EVENT_NAME,
            [MonitoringListenerInterface::SERVICE_ID, 'qtiTestStatusChanged']
        );
        $eventManager->attach(
            AuthorizationGranted::EVENT_NAME,
            [MonitoringListenerInterface::SERVICE_ID, 'deliveryAuthorized']
        );
    }

    private function registerMonitoringRepository(): void
    {
        $monitoringStorage = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        if (is_a($monitoringStorage, 'oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService', true)) {
            $options = $monitoringStorage->getOptions();
            $this->registerService(DeliveryMonitoringService::SERVICE_ID, new MonitoringRepository($options));
        }
    }

    private function registerMonitoringListener(): void
    {
        $this->registerService(MonitoringListenerInterface::SERVICE_ID, new MonitoringListener());
    }
}

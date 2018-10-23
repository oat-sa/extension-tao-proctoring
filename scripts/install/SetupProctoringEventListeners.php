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
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\service\ServiceManager;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoProctoring\model\delivery\DeliverySyncService;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoProctoring\model\monitorCache\update\TestUpdate;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\tao\model\event\MetadataModified;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\monitorCache\update\TestTakerUpdate;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;

/**
 * Class RegisterSessionStateListener
 * @package oat\taoProctoring\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SetupProctoringEventListeners extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        // monitoring cache
        $this->registerEvent(DeliveryExecutionState::class, [DeliveryMonitoringService::SERVICE_ID, 'executionStateChanged']);
        $this->registerEvent(DeliveryExecutionCreated::class, [DeliveryMonitoringService::SERVICE_ID, 'executionCreated']);
        $this->registerEvent(MetadataModified::class, [DeliveryMonitoringService::SERVICE_ID, 'deliveryLabelChanged']);
        $this->registerEvent(TestChangedEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'testStateChanged']);
        $this->registerEvent(QtiTestStateChangeEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'qtiTestStatusChanged']);
        $this->registerEvent(AuthorizationGranted::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'deliveryAuthorized']);
        $this->registerEvent(MetadataModified::class, [TestTakerUpdate::class, 'propertyChange']);

        $this->registerEvent(TestExecutionPausedEvent::class, [DeliveryExecutionStateService::SERVICE_ID, 'catchSessionPause']);

        $this->registerEvent(QtiTestStateChangeEvent::class, [DeliveryHelper::class, 'testStateChanged']);

        $this->registerEvent('oat\\taoProctoring\\model\\event\\DeliveryExecutionFinished', [LoggerService::class, 'logEvent']);

        /**
         * Need to check http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible for every action and setting correct value.
         */
        $this->registerEvent(DeliveryCreatedEvent::class, [DeliverySyncService::SERVICE_ID, 'onDeliveryCreated']);
        $this->registerEvent(DeliveryUpdatedEvent::class, [DeliverySyncService::SERVICE_ID, 'onDeliveryUpdated']);
    }
}


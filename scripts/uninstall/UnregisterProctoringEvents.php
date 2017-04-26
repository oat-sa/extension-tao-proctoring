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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Joel Bout, <joel@taotesting.com>
 */

namespace oat\taoProctoring\scripts\uninstall;


use oat\oatbox\extension\UninstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
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

class UnregisterProctoringEvents extends UninstallAction
{
    public function __invoke($params) {
        
        $this->unregisterEvent(DeliveryExecutionState::class, [DeliveryMonitoringService::SERVICE_ID, 'executionStateChanged']);
        $this->unregisterEvent(DeliveryExecutionCreated::class, [DeliveryMonitoringService::SERVICE_ID, 'executionCreated']);
        $this->unregisterEvent(MetadataModified::class, [DeliveryMonitoringService::SERVICE_ID, 'deliveryLabelChanged']);
        $this->unregisterEvent(TestChangedEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'testStateChanged']);
        $this->unregisterEvent(QtiTestStateChangeEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'qtiTestStatusChanged']);
        $this->unregisterEvent(AuthorizationGranted::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'deliveryAuthorized']);
        $this->unregisterEvent(MetadataModified::class, [TestTakerUpdate::class, 'propertyChange']);

        $this->unregisterEvent(TestExecutionPausedEvent::class, [DeliveryExecutionStateService::SERVICE_ID, 'catchSessionPause']);

        $this->unregisterEvent(QtiTestStateChangeEvent::class, [DeliveryHelper::class, 'testStateChanged']);
        
    }
}

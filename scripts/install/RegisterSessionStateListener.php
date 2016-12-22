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
use oat\taoProctoring\model\monitorCache\update\DeliveryExecutionStateUpdate;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoProctoring\model\monitorCache\update\TestUpdate;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\tao\model\event\MetadataModified;

/**
 * Class RegisterSessionStateListener
 * @package oat\taoProctoring\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterSessionStateListener extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $this->registerEvent(DeliveryExecutionStateUpdate::class, [DeliveryMonitoringService::CONFIG_ID, 'executionStateChanged']);
        $this->registerEvent(DeliveryExecutionCreated::class, [DeliveryMonitoringService::CONFIG_ID, 'executionCreated']);
        $this->registerEvent(TestExecutionPausedEvent::class, [DeliveryExecutionStateService::class, 'catchSessionPause']);
        
        $this->registerEvent(MetadataModified::class, [DeliveryMonitoringService::CONFIG_ID, 'deliveryLabelChange']);
        
    }
}


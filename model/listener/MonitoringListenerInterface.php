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
use Exception;
use oat\tao\model\event\MetadataModified;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoTests\models\event\TestChangedEvent;

interface MonitoringListenerInterface
{
    public const SERVICE_ID = 'taoProctoring/MonitoringListener';

    /**
     * @throws common_exception_NotFound
     */
    public function executionCreated(DeliveryExecutionCreated $event): void;

    /**
     * @throws common_exception_Error|common_exception_NotFound|Exception
     */
    public function executionStateChanged(DeliveryExecutionState $event): void;
    /**
     * Something changed in the state of the test execution (for example: the current item in the test)
     *
     * @throws common_exception_NotFound|common_exception_Error
     */
    public function testStateChanged(TestChangedEvent $event): void;

    /**
     * The status of the test execution has change (for example: from running to paused)
     *
     * @throws common_exception_NotFound
     */
    public function qtiTestStatusChanged(QtiTestStateChangeEvent $event): void;

    /**
     * Update the label of the delivery across the entry cache
     *
     * @param MetadataModified $event
     */
    public function deliveryLabelChanged(MetadataModified $event): void;

    /**
     * Set the proctor who authorized this delivery execution
     *
     * @throws common_exception_NotFound
     */
    public function deliveryAuthorized(AuthorizationGranted $event): void;

    /**
     * @throws common_exception_NotFound
     */
    public function catchTestReactivatedEvent(DeliveryExecutionReactivated $event): void;
}

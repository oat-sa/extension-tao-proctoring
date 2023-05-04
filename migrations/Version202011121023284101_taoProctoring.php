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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoProctoring\model\listener\DeliveryExecutionStateListener;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202011121023284101_taoProctoring extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register event handlers to update state in delivery monitoring when test session status changes.';
    }

    public function up(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->attach(
            DeliveryExecutionState::class,
            [
                DeliveryExecutionStateListener::class,
                'updateRemainingTime'
            ]
        );

        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->detach(
            DeliveryExecutionState::class,
            [
                DeliveryExecutionStateListener::class,
                'updateRemainingTime'
            ]
        );

        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }
}

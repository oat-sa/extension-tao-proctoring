<?php

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

        $this->getServiceLocator()->register(EventManager::SERVICE_ID , $eventManager);
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

        $this->getServiceLocator()->register(EventManager::SERVICE_ID , $eventManager);
    }
}

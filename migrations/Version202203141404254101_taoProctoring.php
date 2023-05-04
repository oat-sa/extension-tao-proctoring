<?php

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoProctoring\model\execution\DeliveryExecutionList;
use oat\taoProctoring\model\execution\DeliveryExecutionListInterface;

final class Version202203141404254101_taoProctoring extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register `DeliveryExecutionList` by interface for overriding possibility';
    }

    public function up(Schema $schema): void
    {
        $this->registerService(DeliveryExecutionListInterface::SERVICE_ID, new DeliveryExecutionList());
    }

    public function down(Schema $schema): void
    {
        $this->getServiceManager()->unregister(DeliveryExecutionListInterface::SERVICE_ID);
    }
}

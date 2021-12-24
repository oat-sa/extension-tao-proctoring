<?php

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\SyncModels;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202112231653214101_taoProctoring extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update the RDF models.';
    }

    public function up(Schema $schema): void
    {
        $this->addReport(
            $this->propagate(
                new SyncModels()
            )([])
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'A manual ontology definition update and synchronization of the RDF models is required in order to revert this migration.'
        );
    }
}

<?php

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Exception;
use common_report_Report as Report;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use oat\generis\persistence\PersistenceManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoProctoring\model\monitorCache\implementation\SimpleMonitoringStorage;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202106081338544101_taoProctoring extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Creates new `extra_data` column in `delivery_monitoring` to store extra data.';
    }

    public function up(Schema $schema): void
    {
        try {
            $persistence = $this->getPersistence();

            $platformName = $persistence->getPlatForm()->getName();
            if ($platformName == 'mysql') {
                $schema = $persistence->getSchemaManager()->createSchema();
                $fromSchema = clone $schema;

                $table = $schema->getTable(SimpleMonitoringStorage::TABLE_NAME);
                $table->addColumn(SimpleMonitoringStorage::COLUMN_EXTRA_DATA, Types::JSON, array("notnull" => false));
                $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);
            } else if ($platformName == 'postgresql') {
                $query = sprintf(
                    'ALTER TABLE %s ADD COLUMN %s jsonb',
                    SimpleMonitoringStorage::TABLE_NAME,
                    SimpleMonitoringStorage::COLUMN_EXTRA_DATA
                );
                $persistence->exec($query);
            } else {
                throw new Exception("Unsupported platform: $platformName");
            }
            $this->addReport(Report::createSuccess('`extra_data` column was created in `delivery_monitoring` table'));
        } catch (Exception $e) {
            $this->addReport(
                new Report(
                    Report::TYPE_ERROR,
                    'Failed to create `extra_data` column in `delivery_monitoring`',
                    [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace(),
                    ]
                )
            );
        }

    }

    public function down(Schema $schema): void
    {
        $persistence = $this->getPersistence();
        $schema = $persistence->getSchemaManager()->createSchema();
        $fromSchema = clone $schema;

        $table = $schema->getTable(SimpleMonitoringStorage::TABLE_NAME);
        if ($table->hasColumn(SimpleMonitoringStorage::COLUMN_EXTRA_DATA)) {
            $table->dropColumn(SimpleMonitoringStorage::COLUMN_EXTRA_DATA);
        }
        $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);
        $this->addReport(Report::createSuccess('`extra_data` column was deleted in `delivery_monitoring` table'));
    }

    private function getPersistence(): common_persistence_SqlPersistence
    {
        return $this->getServiceLocator()
            ->get(PersistenceManager::SERVICE_ID)
            ->getPersistenceById('default');
    }
}

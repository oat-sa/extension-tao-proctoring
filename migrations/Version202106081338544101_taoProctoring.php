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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Exception;
use oat\oatbox\reporting\Report as Report;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use oat\generis\persistence\PersistenceManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoProctoring\model\repository\MonitoringRepository;

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

                $table = $schema->getTable(MonitoringRepository::TABLE_NAME);
                $table->addColumn(MonitoringRepository::COLUMN_EXTRA_DATA, Types::JSON, array("notnull" => false));
                $persistence->getPlatForm()->migrateSchema($fromSchema, $schema);
            } elseif ($platformName == 'postgresql') {
                $query = sprintf(
                    'ALTER TABLE %s ADD COLUMN %s jsonb',
                    MonitoringRepository::TABLE_NAME,
                    MonitoringRepository::COLUMN_EXTRA_DATA
                );
                $persistence->exec($query);
            } else {
                throw new Exception("Unsupported platform: $platformName");
            }
            $this->addReport(new Report(Report::TYPE_SUCCESS, '`extra_data` column was created in `delivery_monitoring` table'));
            $this->addReport(new Report(
                Report::TYPE_INFO,
                'You can now change configuration to use this new column via: ' . PHP_EOL .
                '`php index.php \'\oat\taoProctoring\scripts\tools\MonitoringExtraFieldConfigurationMigration\'`'
            ));
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

        $table = $schema->getTable(MonitoringRepository::TABLE_NAME);
        if ($table->hasColumn(MonitoringRepository::COLUMN_EXTRA_DATA)) {
            $table->dropColumn(MonitoringRepository::COLUMN_EXTRA_DATA);
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

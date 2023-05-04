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

namespace oat\taoProctoring\scripts\install\db;

use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\SchemaException;
use common_Logger;
use oat\taoProctoring\model\repository\MonitoringRepository;

class DbSetup
{
    public function generateTable(common_persistence_SqlPersistence $persistence)
    {
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableLog = $schema->createTable(MonitoringRepository::TABLE_NAME);
            $tableLog->addOption('engine', 'InnoDB');
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID,
                "string",
                ["notnull" => true, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_STATUS,
                "string",
                ["notnull" => true, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_CURRENT_ASSESSMENT_ITEM,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_TEST_TAKER,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_AUTHORIZED_BY,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_START_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_END_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_TEST_TAKER_FIRST_NAME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::COLUMN_TEST_TAKER_LAST_NAME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::DELIVERY_ID,
                "text",
                ["notnull" => false]
            );
            $tableLog->addColumn(
                MonitoringRepository::DELIVERY_NAME,
                "text",
                ["notnull" => false]
            );
            $tableLog->addColumn(
                MonitoringRepository::LAST_TEST_TAKER_ACTIVITY,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::REMAINING_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::EXTENDED_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::EXTRA_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::CONSUMED_EXTRA_TIME,
                "string",
                ["notnull" => false, "length" => 255]
            );
            $tableLog->addColumn(
                MonitoringRepository::ITEM_DURATION,
                "string",
                ["notnull" => false, "length" => 32]
            );
            $tableLog->addColumn(
                MonitoringRepository::STORED_ITEM_DURATION,
                "string",
                ["notnull" => false, "length" => 32]
            );

            if (in_array($persistence->getPlatForm()->getName(), ['mysql','sqlite'])) {
                $tableLog->addColumn(MonitoringRepository::COLUMN_EXTRA_DATA, 'json', array("notnull" => false));
            }

            $tableLog->setPrimaryKey(array(MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID));

            $tableLog->addIndex(
                array(MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID),
                'IDX_' . MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID . '_'
                    . MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID
            );
            $tableLog->addIndex(
                array(MonitoringRepository::COLUMN_START_TIME),
                'IDX_' . MonitoringRepository::TABLE_NAME . '_' . MonitoringRepository::COLUMN_START_TIME
            );
            $tableLog->addIndex(
                array(MonitoringRepository::COLUMN_END_TIME),
                'IDX_' . MonitoringRepository::TABLE_NAME . '_' . MonitoringRepository::COLUMN_END_TIME
            );
            $tableLog->addUniqueIndex(
                array(MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID),
                'IDX_' . MonitoringRepository::TABLE_NAME . '_' . MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID
                    . '_UNIQUE'
            );
        } catch (SchemaException $e) {
            common_Logger::i('Database Schema already up to date.');
        }

        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);

        if ($persistence->getPlatForm()->getName() == 'postgresql') {
            $queries[] = sprintf(
                'ALTER TABLE %s ADD COLUMN %s jsonb',
                MonitoringRepository::TABLE_NAME,
                MonitoringRepository::COLUMN_EXTRA_DATA
            );
        }

        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * Returns a list of columns stored in the primary table
     */
    public function getPrimaryColumns(): array
    {
        return [
            MonitoringRepository::COLUMN_DELIVERY_EXECUTION_ID,
            MonitoringRepository::COLUMN_STATUS,
            MonitoringRepository::COLUMN_CURRENT_ASSESSMENT_ITEM,
            MonitoringRepository::COLUMN_TEST_TAKER,
            MonitoringRepository::COLUMN_AUTHORIZED_BY,
            MonitoringRepository::COLUMN_START_TIME,
            MonitoringRepository::COLUMN_END_TIME,
            MonitoringRepository::COLUMN_TEST_TAKER_FIRST_NAME,
            MonitoringRepository::COLUMN_TEST_TAKER_LAST_NAME,
            MonitoringRepository::DELIVERY_ID,
            MonitoringRepository::DELIVERY_NAME,
            MonitoringRepository::LAST_TEST_TAKER_ACTIVITY,
            MonitoringRepository::REMAINING_TIME,
            MonitoringRepository::EXTENDED_TIME,
            MonitoringRepository::EXTRA_TIME,
            MonitoringRepository::CONSUMED_EXTRA_TIME,
            MonitoringRepository::ITEM_DURATION,
            MonitoringRepository::STORED_ITEM_DURATION,
        ];
    }
}

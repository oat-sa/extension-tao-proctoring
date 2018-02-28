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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoProctoring\scripts\install\db;

use Doctrine\DBAL\Schema\SchemaException;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use common_Logger;

class DbSetup {
    
    public static function generateTable(\common_persistence_SqlPersistence $persistence)
    {

        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        
        try {
            $tableLog = $schema->createTable(MonitoringStorage::TABLE_NAME);
            $tableLog->addOption('engine', 'InnoDB');
            $tableLog->addColumn(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, "string", array("notnull" => true, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_STATUS, "string", array("notnull" => true, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_CURRENT_ASSESSMENT_ITEM, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_TEST_TAKER, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_AUTHORIZED_BY, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_START_TIME, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_END_TIME, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_TEST_TAKER_FIRST_NAME, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::COLUMN_TEST_TAKER_LAST_NAME, "string", array("notnull" => false, "length" => 255));
            $tableLog->addColumn(MonitoringStorage::DELIVERY_ID, "text", array("notnull" => false));
            $tableLog->addColumn(MonitoringStorage::DELIVERY_NAME, "text", array("notnull" => false));

            $tableLog->setPrimaryKey(array(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID));
        
            $tableLog->addIndex(
                array(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID),
                'IDX_' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . '_' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID
            );
            $tableLog->addIndex(
                array(MonitoringStorage::COLUMN_START_TIME),
                'IDX_' . MonitoringStorage::TABLE_NAME . '_' . MonitoringStorage::COLUMN_START_TIME
            );
            $tableLog->addIndex(
                array(MonitoringStorage::COLUMN_END_TIME),
                'IDX_' . MonitoringStorage::TABLE_NAME . '_' . MonitoringStorage::COLUMN_END_TIME
            );
            $tableLog->addUniqueIndex(
                array(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID),
                'IDX_' . MonitoringStorage::TABLE_NAME . '_' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . '_UNIQUE'
            );
        
            $tableData = $schema->createTable(MonitoringStorage::KV_TABLE_NAME);
            $tableData->addOption('engine', 'InnoDB');
            $tableData->addColumn(MonitoringStorage::KV_COLUMN_PARENT_ID, "string", array("notnull" => true, "length" => 255));
            $tableData->addColumn(MonitoringStorage::KV_COLUMN_KEY, "string", array("notnull" => true, "length" => 255));
            $tableData->addColumn(MonitoringStorage::KV_COLUMN_VALUE, "text", array("notnull" => false));
        
            $tableData->setPrimaryKey(array(MonitoringStorage::KV_COLUMN_PARENT_ID, MonitoringStorage::KV_COLUMN_KEY));
        
            $tableData->addForeignKeyConstraint(
                $tableLog,
                array(MonitoringStorage::KV_COLUMN_PARENT_ID),
                array(MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID),
                array(
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE',
                ),
                MonitoringStorage::KV_FK_PARENT
            );
        } catch(SchemaException $e) {
            common_Logger::i('Database Schema already up to date.');
        }
        
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * Returns a list of columns stored in the primary table
     *
     * @return array column identifiers
     */
    public static function getPrimaryColumns()
    {
        return [
            MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID,
            MonitoringStorage::COLUMN_STATUS,
            MonitoringStorage::COLUMN_CURRENT_ASSESSMENT_ITEM,
            MonitoringStorage::COLUMN_TEST_TAKER,
            MonitoringStorage::COLUMN_AUTHORIZED_BY,
            MonitoringStorage::COLUMN_START_TIME,
            MonitoringStorage::COLUMN_END_TIME,
            MonitoringStorage::DELIVERY_ID,
            MonitoringStorage::DELIVERY_NAME,
        ];
    }
}

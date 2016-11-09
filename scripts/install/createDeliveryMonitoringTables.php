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

use Doctrine\DBAL\Schema\SchemaException;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;

$persistence = common_persistence_Manager::getPersistence('default');

$schemaManager = $persistence->getDriver()->getSchemaManager();
$schema = $schemaManager->createSchema();
$fromSchema = clone $schema;

try {
    $tableLog = $schema->createTable(DeliveryMonitoringService::TABLE_NAME);
    $tableLog->addOption('engine', 'InnoDB');
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID, "string", array("notnull" => true, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_STATUS, "string", array("notnull" => true, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_CURRENT_ASSESSMENT_ITEM, "string", array("notnull" => false, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_TEST_TAKER, "string", array("notnull" => false, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_AUTHORIZED_BY, "string", array("notnull" => false, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_START_TIME, "string", array("notnull" => false, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_END_TIME, "string", array("notnull" => false, "length" => 255));
    $tableLog->addColumn(DeliveryMonitoringService::COLUMN_REMAINING_TIME, "string", array("notnull" => false, "length" => 255));

    $tableLog->setPrimaryKey(array(DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID));

    $tableLog->addIndex(
        array(DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID),
        'IDX_' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '_' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID
    );
    $tableLog->addIndex(
        array(DeliveryMonitoringService::COLUMN_START_TIME),
        'IDX_' . DeliveryMonitoringService::TABLE_NAME . '_' . DeliveryMonitoringService::COLUMN_START_TIME
    );
    $tableLog->addIndex(
        array(DeliveryMonitoringService::COLUMN_END_TIME),
        'IDX_' . DeliveryMonitoringService::TABLE_NAME . '_' . DeliveryMonitoringService::COLUMN_END_TIME
    );
    $tableLog->addUniqueIndex(
        array(DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID),
        'IDX_' . DeliveryMonitoringService::TABLE_NAME . '_' . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '_UNIQUE'
    );

    $tableData = $schema->createTable(DeliveryMonitoringService::KV_TABLE_NAME);
    $tableData->addOption('engine', 'InnoDB');
    $tableData->addColumn(DeliveryMonitoringService::KV_COLUMN_PARENT_ID, "string", array("notnull" => true, "length" => 255));
    $tableData->addColumn(DeliveryMonitoringService::KV_COLUMN_KEY, "string", array("notnull" => true, "length" => 255));
    $tableData->addColumn(DeliveryMonitoringService::KV_COLUMN_VALUE, "text", array("notnull" => false));

    $tableData->setPrimaryKey(array(DeliveryMonitoringService::KV_COLUMN_PARENT_ID, DeliveryMonitoringService::KV_COLUMN_KEY));

    $tableData->addForeignKeyConstraint(
        $tableLog,
        array(DeliveryMonitoringService::KV_COLUMN_PARENT_ID),
        array(DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID),
        array(
            'onDelete' => 'CASCADE',
            'onUpdate' => 'CASCADE',
        ),
        DeliveryMonitoringService::KV_FK_PARENT
    );
} catch(SchemaException $e) {
    common_Logger::i('Database Schema already up to date.');
}

$queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
foreach ($queries as $query) {
    $persistence->exec($query);
}

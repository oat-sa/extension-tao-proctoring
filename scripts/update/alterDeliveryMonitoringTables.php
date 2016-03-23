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

namespace oat\taoProctoring\scripts\update;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;

class AlterDeliveryMonitoringTables extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $persistence = \common_persistence_Manager::getPersistence('default');

        // Drop foreign key
        /** @var common_persistence_sql_pdo_SchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        try {
            $tableData = $schema->getTable(DeliveryMonitoringService::KV_TABLE_NAME);
            $tableData->removeForeignKey(DeliveryMonitoringService::KV_FK_PARENT);
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        //change parent_id column type
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        try {
            $tableData = $schema->getTable(DeliveryMonitoringService::KV_TABLE_NAME);
            $tableData->changeColumn(DeliveryMonitoringService::KV_COLUMN_PARENT_ID, array('type' => Type::getType('string'), 'notnull' => true, 'length' => 255));
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        //update parent_id column values
        $this->updateLinks();

        //add foreign key.
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        try {
            $tableLog = $schema->getTable(DeliveryMonitoringService::TABLE_NAME);
            $tableData = $schema->getTable(DeliveryMonitoringService::KV_TABLE_NAME);

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
            \common_Logger::i('Database Schema already up to date.');
        }
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Tables successfully altered'));
    }

    protected function updateLinks()
    {
        $persistence = \common_persistence_Manager::getPersistence('default');
        $stmt = $persistence->query('SELECT * FROM delivery_monitoring');
        $parentRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($parentRows as $parentRow) {
            $persistence->exec("UPDATE kv_delivery_monitoring SET parent_id='{$parentRow['delivery_execution_id']}'
              WHERE parent_id={$parentRow['id']}
            ");
        }
    }
}

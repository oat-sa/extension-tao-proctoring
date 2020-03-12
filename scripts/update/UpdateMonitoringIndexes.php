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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoProctoring\scripts\update;

use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\action\Action;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class UpdateMonitoringIndexes implements Action
{
    protected $persistence;

    public function __invoke($params)
    {
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);

        /** @var PersistenceManager $persistenceManager */
        $persistenceManager = ServiceManager::getServiceManager()->get(PersistenceManager::SERVICE_ID);
        $this->persistence = $persistenceManager->getPersistenceById($service->getOption(MonitoringStorage::OPTION_PERSISTENCE));

        $schemaManager = $this->persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        try {
            $tableLog = $schema->getTable(MonitoringStorage::TABLE_NAME);
            $tableLog->addIndex(
                [MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID, MonitoringStorage::COLUMN_START_TIME],
                'idx_' . MonitoringStorage::TABLE_NAME . '_' . MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID . '_' . MonitoringStorage::COLUMN_START_TIME
            );

            $tableData = $schema->getTable(MonitoringStorage::KV_TABLE_NAME);
            $tableData->addIndex(
                [MonitoringStorage::KV_COLUMN_VALUE, MonitoringStorage::KV_COLUMN_KEY, MonitoringStorage::KV_COLUMN_PARENT_ID],
                'idx_' . MonitoringStorage::KV_TABLE_NAME . '_value_key_parent'
            );
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $queries = $this->persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $this->persistence->exec($query);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Tables successfully altered'));
    }
}

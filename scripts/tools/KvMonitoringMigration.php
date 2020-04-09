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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\tools;


use oat\oatbox\extension\script\ScriptAction;
use common_report_Report as Report;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class KvMonitoringMigration extends ScriptAction
{

    protected function provideOptions()
    {
        return [
            'fields' => [
                'prefix' => 'f',
                'longPrefix' => 'fields',
                'required' => true,
                'description' => 'A string contains coma separated list of target fields'
            ],

            'dataLength' => [
                'prefix' => 'l',
                'longPrefix' => 'dataLength',
                'required' => false,
                'description' => 'Data length. If present new column(s) will have type VARCHAR($length), otherwise - TEXT column will be created'
            ],

            'deleteKV' => [
                'prefix' => 'd',
                'longPrefix' => 'deleteKV',
                'defaultValue' => 0,
                'cast' => 'integer',
                'required' => false,
                'description' => 'Indicate whether or not records from KV strorage should be removed'
            ],

            'strictMode' => [
                'prefix' => 's',
                'longPrefix' => 'strictMode',
                'defaultValue' => 1,
                'cast' => 'integer',
                'required' => false,
                'description' => 'Allow to skip creation of already existing in RDBS columns, should help resuming interrupted migrations '
            ],

            'persistConfig' => [
                'flag' => true,
                'prefix' => 'pc',
                'longPrefix' => 'persistConfig',
                'defaultValue' => 1,
                'description' => 'Indicate whether or not changes in config should be recorded. Please note that applies to the current instance only and changes MUST be distributed across all of them'
            ],

            'resume' => [
                'flag' => true,
                'prefix' => 'r',
                'longPrefix' => 'resume',
                'defaultValue' => 1,
                'description' => 'Indicate whether or not changes processing should be resumed from the last processed frame'
            ],

            'chunkSize' => [
                'prefix' => 'c',
                'defaultValue' => 100,
                'longPrefix' => 'chunk-size',
                'cast' => 'integer',
                'required' => false,
                'description' => 'Specifies delivery executions amount for processing (chunk size) per iteration for migration'
            ],
        ];
    }

    protected function provideDescription()
    {
        return 'This tools take care about KV monitoring data migration to the relational table';
    }

    /**
     * Run Script.
     *
     *
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    protected function run()
    {
        $subReport = new Report(Report::TYPE_INFO);
        $removeKV = $this->getOption('deleteKV');
        $persistConfig = $this->getOption('persistConfig');
        $chunkSize = $this->getOption('chunkSize');
        $resume = $this->getOption('resume');

        /** @var MonitoringStorage $monitoringService */
        $monitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $originalPrimaryColumns = $monitoringService->getOption(MonitoringStorage::OPTION_PRIMARY_COLUMNS);
        $fields = array_unique(explode(',', $this->getOption('fields')));
        $kvFields = array_diff($fields, $originalPrimaryColumns);
        if (empty($kvFields)) {
            return new Report(
                Report::TYPE_INFO,
                'Nothing to migrate'
            );
        }

        $dataLength = 0;
        if ($this->hasOption('dataLength')) {
            $dataLength = (int) $this->getOption('dataLength');
        }
        foreach ($kvFields as $field) {
            $createColumnReport = $this->addColumn($field, $dataLength);
            $subReport->add($createColumnReport);
        }

        $total = $monitoringService->count([]);
        $offset = 0;

        if ($resume) {
            $offset = (int)$this->getCache()->get(__CLASS__ . 'offset');
        }

        $executionProcessed = 0;
        $removed = 0;

        while ($offset < $total ) {
            $this->getCache()->set(__CLASS__ . 'offset', $offset);

            $options = [
                'limit' => $chunkSize,
                'offset' => $offset
            ];
            $monitoringService->setOption(MonitoringStorage::OPTION_PRIMARY_COLUMNS, $originalPrimaryColumns);
            $deliveryExecutionsData = $monitoringService->find([], $options, true);
            $monitoringService->setOption(MonitoringStorage::OPTION_PRIMARY_COLUMNS, array_merge($originalPrimaryColumns, $kvFields));
            foreach ($deliveryExecutionsData as $dd) {
                $monitoringService->save($dd);
                $executionProcessed++;

                if ($removeKV) {
                    $sql = 'DELETE FROM ' . MonitoringStorage::KV_TABLE_NAME . '
                    WHERE ' . MonitoringStorage::KV_COLUMN_KEY . ' IN (' . implode(',', array_fill(0, count($kvFields), '?')) . ')' .
                        'AND ' . MonitoringStorage::KV_COLUMN_PARENT_ID . ' =?';
                    $removed += $monitoringService->getPersistence()->exec($sql, array_merge($kvFields, [$dd->get()[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]]));
                }
            }
            $offset += $chunkSize;
        }
        $subReport->add(Report::createSuccess(__('%s executions migrated', $executionProcessed)));

        if ($removeKV) {
            $subReport->add(Report::createSuccess(__('%s KV entries removed', $removed)));
        }

        if ($persistConfig) {
            $monitoringService->setOption(MonitoringStorage::OPTION_PRIMARY_COLUMNS, array_merge($originalPrimaryColumns, $kvFields));
            $this->getServiceManager()->register(DeliveryMonitoringService::SERVICE_ID, $monitoringService);
            $subReport->add(Report::createSuccess('Config persisted (ONLY ON THE CURRENT SERVER. UPDATE CONFIGS ON ALL OTHER SERVERS)'));
        }

        // And return a Report!
        $result = new Report(
            Report::TYPE_SUCCESS,
            'Thanks for using migration tool!'
        );
        $result->add($subReport);

        $this->getCache()->set(__CLASS__ . 'offset', 0);

        return $result;
    }

    protected function provideUsage()
    {
        // Overriding this method is option. Simply describe which option prefixes have to
        // to be used in order to display the usage of the script to end user.
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    protected function addColumn(string $columnName, int $dataLength)
    {
        $strictMode = $this->getOption('strictMode');

        $monitorService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById($monitorService->getOption(MonitoringStorage::OPTION_PERSISTENCE));
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableData = $schema->getTable(MonitoringStorage::TABLE_NAME);
            $options = ['notnull' => false];
            $columnType = 'text';
            if ($dataLength > 0) {
                $columnType = 'string';
                $options['length'] = $dataLength;
            }

            $tableData->addColumn($columnName, $columnType, $options);
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        } catch (\Exception $exception) {
            if ($strictMode) {
                throw $exception;
            }
        }
        return Report::createSuccess(__('Column %s successfully created', $columnName));

    }

    /**
     * @return common_persistence_KeyValuePersistence|\common_persistence_Persistence
     */
    private function getCache()
    {
        return \common_persistence_KeyValuePersistence::getPersistence('cache');
    }
}
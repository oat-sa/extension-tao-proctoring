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

namespace oat\taoProctoring\scripts\tools;

use Exception;
use oat\oatbox\extension\script\ScriptAction;
use common_report_Report as Report;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\SimpleMonitoringStorage;
use PDO;

class MonitoringExtraFieldMigration extends ScriptAction
{
    /** @var SimpleMonitoringStorage */
    private $monitoringService;

    /** @var int */
    private $updated = 0;

    /** @var int */
    private $deleted = 0;

    protected function provideOptions()
    {
        return [
            'wet-run' => [
                'flag' => true,
                'prefix' => 'w',
                'longPrefix' => 'wet-run',
                'description' => 'Use this option to persist data. Default is dry-run mode'
            ],

            'chunkSize' => [
                'prefix' => 'c',
                'defaultValue' => 100,
                'longPrefix' => 'chunk-size',
                'cast' => 'integer',
                'required' => false,
                'description' => 'Number of delivery_execution migrated processed by iteration.'
            ],

            'deleteKv' => [
                'flag' => true,
                'longPrefix' => 'deleteKv',
                'defaultValue' => 0,
                'description' => 'Indicate if the script should remove migrated data.'
            ],
        ];
    }

    protected function provideDescription()
    {
        return 'This tools take care about KV monitoring data migration to the relational table as extra_data column';
    }

    protected function run()
    {
        $this->monitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        try {
            $this->validateMigrationCanBeDone();
        } catch (Exception $e) {
            return Report::createFailure(sprintf('Enable to perform migration with error: %s', $e->getMessage()));
        }

        $count = $this->monitoringService->count();
        $chunk = ceil($count / $this->getOption('chunkSize'));

        $this->printMigrationScriptOptions($count, (int) $chunk);

        $offset = 0;

        try {
            for ($i = 0; $i < $chunk; $i++) {
                $deliveryExecutions = $this->findDeliveryExecutionsByChunk($offset);

                foreach ($deliveryExecutions as $deliveryExecution) {
                    $kvData = $this->fetchDeliveryExecutionExtraData($deliveryExecution);

                    $this->migrateDeliveryExecutionKvDataToExtraColumn($deliveryExecution);

                    $this->deleteDeliveryExecutionKvData($deliveryExecution, $kvData);
                }
            }
        } catch (Exception $e) {
            return new Report(Report::TYPE_ERROR, $e->getMessage());
        }

        return $this->createScriptReport();
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

    /**
     * Check if service is already configured to use JSON column and if column `extra_field` exists
     *
     * @throws Exception
     */
    private function validateMigrationCanBeDone(): void
    {
        if (!$this->monitoringService instanceof SimpleMonitoringStorage) {
            throw new Exception('DeliveryMonitoringService is not implementing SimpleMonitoringStorage. Migration aborted');
        }

        $table = $this->monitoringService
            ->getPersistence()
            ->getDriver()
            ->getSchemaManager()
            ->createSchema()
            ->getTable(SimpleMonitoringStorage::TABLE_NAME);

        if (!$table->hasColumn(SimpleMonitoringStorage::COLUMN_EXTRA_DATA)) {
            throw new Exception(sprintf('Column %s does not exist. Migration aborted', SimpleMonitoringStorage::COLUMN_EXTRA_DATA));
        }
    }

    private function printMigrationScriptOptions(int $countOfDeliveryExecution, int $chunk) :void
    {
        echo 'Script options: ' . PHP_EOL;
        echo sprintf(' - DRY RUN mode: %s', $this->getOption('wet-run') ? 'no' : 'yes') . PHP_EOL;
        echo sprintf(' - Delete KV data: %s', $this->getOption('deleteKv') ? 'yes' : 'no') . PHP_EOL;
        echo sprintf(' - Number of delivery executions found: %s', $countOfDeliveryExecution) . PHP_EOL;
        echo sprintf(' - Chunk size: %s', $this->getOption('chunkSize')) . PHP_EOL;
        echo sprintf(' - Number of iteration: %s', $chunk) . PHP_EOL;
    }

    private function findDeliveryExecutionsByChunk(int &$offset): array
    {
        $chunkSize = $this->getOption('chunkSize');

        $options = [
            'limit' => $chunkSize,
            'offset' => $offset,
        ];

        $deliveryExecutions = $this->monitoringService->find([], $options);

        if (!$this->getOption('deleteKv')) {
            $offset += $chunkSize;
        }

        // Moving percentage
        echo sprintf('%s delivery executions fetched.', count($deliveryExecutions)) . PHP_EOL;

        return $deliveryExecutions;
    }

    private function fetchDeliveryExecutionExtraData(DeliveryMonitoringData $deliveryExecution): array
    {
        $stmt = $this->monitoringService->getPersistence()->query(
            'SELECT monitoring_key, monitoring_value FROM kv_delivery_monitoring WHERE parent_id = ?',
            [
                $deliveryExecution->get()[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
            ]
        );

        $kvData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($kvData as $data) {
            $deliveryExecution->addValue($data['monitoring_key'], $data['monitoring_value']);
        }
        return $kvData;
    }

    /**
     * @throws Exception
     */
    private function migrateDeliveryExecutionKvDataToExtraColumn(DeliveryMonitoringData $deliveryExecution)
    {
        if ($this->getOption('wet-run')) {
            if ($this->monitoringService->partialSave($deliveryExecution)) {
                $this->updated++;
            } else {
                throw new Exception(sprintf(
                    'Unable to save delivery execution %s',
                    $deliveryExecution->get()[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
                ));
            }
        } else {
            $this->updated++;
        }
    }

    private function deleteDeliveryExecutionKvData(DeliveryMonitoringData $deliveryExecution, array $kvData): void
    {
        if ($this->getOption('wet-run')) {
            if ($this->getOption('deleteKv')) {
                $this->deleted += $this->monitoringService->getPersistence()->exec(
                    'DELETE FROM kv_delivery_monitoring WHERE parent_id = ?',
                    [$deliveryExecution->get()[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]]
                );
            }
        }  else {
            if ($this->getOption('deleteKv')) {
                $this->deleted += count($kvData);
            }
        }
    }

    private function createScriptReport(): Report
    {
        $wetrun = $this->getOption('wet-run');
        $report = Report::createInfo();
        $report->add(Report::createSuccess(sprintf('Saved delivery execution: %s', $this->updated)));
        $report->add(Report::createSuccess(sprintf('ExtraData removed from KV table: %s', $this->deleted)));
        $report->add(new Report(
            $wetrun ? Report::TYPE_SUCCESS : Report::TYPE_ERROR,
            sprintf('Script runtime executed in `%s` mode', $wetrun ? 'WET_RUN' : 'DRY_RUN')
        ));
        return $report;
    }
}

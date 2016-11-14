<?php

namespace oat\taoProctoring\scripts\install;

use Doctrine\DBAL\Types\Type;
use oat\oatbox\service\ServiceManager;
use Doctrine\DBAL\Schema\SchemaException;
use oat\taoProctoring\model\PaginatedStorage;
use oat\taoProctoring\model\DiagnosticStorage;

class createDiagnosticTable extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $persistence = ServiceManager::getServiceManager()
            ->get(PaginatedStorage::SERVICE_ID)
            ->getPersistence();

        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            if ($schema->hasTable(DiagnosticStorage::DIAGNOSTIC_TABLE)) {
                $tableResults = $schema->getTable(DiagnosticStorage::DIAGNOSTIC_TABLE);
            } else {
                $tableResults = $schema->createTable(DiagnosticStorage::DIAGNOSTIC_TABLE);
            }

            $tableResults->addOption('engine', 'MyISAM');

            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_ID)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_ID, 'string', ['length' => 32]);
            } else {
                $tableResults->changeColumn(DiagnosticStorage::DIAGNOSTIC_ID, ['type' => Type::getType('string'), 'length' => 32]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_TEST_CENTER)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_TEST_CENTER, 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_LOGIN)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_LOGIN, 'string', ['length' => 32]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_WORKSTATION)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_WORKSTATION, 'string', ['length' => 64, 'notnull' => false]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_IP)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_IP, 'string', ['length' => 32]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BROWSER)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BROWSER, 'string', ['length' => 32]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BROWSERVERSION)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BROWSERVERSION, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_OS)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_OS, 'string', ['length' => 32]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_OSVERSION)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_OSVERSION, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_COMPATIBLE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_COMPATIBLE, 'boolean');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_VERSION)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_VERSION, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MIN)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MIN, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MAX)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MAX, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SUM)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SUM, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_COUNT)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_COUNT, 'integer', ['length' => 16]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_AVERAGE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_AVERAGE, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MEDIAN)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MEDIAN, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_VARIANCE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_VARIANCE, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_DURATION)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_DURATION, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SIZE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SIZE, 'integer', ['length' => 16]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MIN)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MIN, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MAX)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MAX, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_SUM)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_SUM, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_COUNT)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_COUNT, 'integer', ['length' => 16]);
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_AVERAGE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_AVERAGE, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MEDIAN)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MEDIAN, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_VARIANCE)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_VARIANCE, 'float');
            }
            if (!$tableResults->hasColumn(DiagnosticStorage::DIAGNOSTIC_CREATED_AT)) {
                $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_CREATED_AT, 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
            }
            if (!$tableResults->hasPrimaryKey(DiagnosticStorage::DIAGNOSTIC_ID)) {
                $tableResults->setPrimaryKey(array(DiagnosticStorage::DIAGNOSTIC_ID));
            }

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date. /' . $e->getMessage());
        }
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Diagnostic successfully created');
    }
}
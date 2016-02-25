<?php

namespace oat\taoProctoring\scripts\install;

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
            $tableResults = $schema->createtable(DiagnosticStorage::DIAGNOSTIC_TABLE);
            $tableResults->addOption('engine', 'MyISAM');

            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_ID, 'string', ['length' => 16]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_TEST_CENTER, 'string', ['length' => 255]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_LOGIN, 'string', ['length' => 32]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_WORKSTATION, 'string', ['length' => 64]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_IP, 'string', ['length' => 32]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BROWSER, 'string', ['length' => 32]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BROWSERVERSION, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_OS, 'string', ['length' => 32]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_OSVERSION, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_COMPATIBLE, 'boolean');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_VERSION, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MIN, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MAX, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SUM, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_COUNT, 'integer', ['length' => 16]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_AVERAGE, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_MEDIAN, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_VARIANCE, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_DURATION, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_BANDWIDTH_SIZE, 'integer', ['length' => 16]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MIN, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MAX, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_SUM, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_COUNT, 'integer', ['length' => 16]);
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_AVERAGE, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_MEDIAN, 'float');
            $tableResults->addColumn(DiagnosticStorage::DIAGNOSTIC_PERFORMANCE_VARIANCE, 'float');
            $tableResults->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);

            $tableResults->setPrimaryKey(array(DiagnosticStorage::DIAGNOSTIC_ID));

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Diagnostic successfully created');
    }
}
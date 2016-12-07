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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\update;

use \common_ext_ExtensionUpdater;
use Doctrine\DBAL\Schema\SchemaException;
use oat\tao\model\entryPoint\EntryPointService;
use oat\tao\model\event\MetadataModified;
use oat\taoProctoring\model\DiagnosticStorage;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\event\EligiblityChanged;
use oat\taoProctoring\model\PaginatedStorage;
use oat\taoProctoring\model\ReasonCategoryService;
use oat\taoProctoring\model\TestCenterService;
use oat\taoProctoring\model\textConverter\ProctoringTextConverter;
use oat\taoProctoring\scripts\install\addDiagnosticSettings;
use oat\taoProctoring\scripts\install\createDiagnosticTable;
use oat\taoProctoring\model\implementation\DeliveryService;
use oat\taoProctoring\model\entrypoint\ProctoringDeliveryServer;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\oatbox\event\EventManager;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoProctoring\model\implementation\DeliveryAuthorizationService;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;
use oat\taoProctoring\scripts\install\RegisterProctoringLog;
use oat\taoProctoring\model\ProctoringAssignmentService;
use oat\taoProctoring\model\DeliveryServerService;
use oat\taoProctoring\model\implementation\TestSessionConnectivityStatusService;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoProctoring\model\authorization\ProctorDeliveryAuthorizationService;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoDelivery\model\authorization\strategy\StateValidation;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\implementation\TestSessionHistoryService;

/**
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends common_ext_ExtensionUpdater {

    /**
     * @param string $initialVersion
     * @return string string
     */
    public function update($initialVersion) {

        $currentVersion = $initialVersion;
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');

        if ($currentVersion == '0.1') {
            $service = new DeliveryService();
            $ext->setConfig('delivery', $service);
            $currentVersion = '0.2';
        }

        if ($currentVersion == '0.2') {
//            $service = new TestCenterService();
//            $ext->setConfig('testCenter', $service);
            $currentVersion = '0.3';
        }

        if ($currentVersion == '0.3') {
            //grant access to test taker
            $testTakerRole = new \core_kernel_classes_Resource(INSTANCE_ROLE_DELIVERY);
            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $accessService->grantModuleAccess($testTakerRole, 'taoProctoring', 'DeliveryServer');

            $mpManagerRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole');
            $accessService->revokeModuleAccess($mpManagerRole, 'taoProctoring', 'DeliveryServer');

            //replace delivery server
            $entryPointService = EntryPointService::getRegistry();
            $entryPointService->overrideEntryPoint('deliveryServer', new ProctoringDeliveryServer());
            $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $entryPointService);
            $currentVersion = '0.4';
        }

        if ($currentVersion == '0.4') {
            OntologyUpdater::syncModels();
            $ext->unsetConfig('testCenter');

            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $roleService = \tao_models_classes_RoleService::singleton();

            //grant access right to proctoring manager
            $testCenterManager = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager');
            $globalManager = new \core_kernel_Classes_Resource('http://www.tao.lu/Ontologies/TAO.rdf#BackOfficeRole');
            $roleService->includeRole($globalManager, $testCenterManager);
            $accessService->grantModuleAccess($testCenterManager, 'taoProctoring', 'TestCenterManager');

            //revoke access to legacy delivery server
            $testTakerRole = new \core_kernel_classes_Resource(INSTANCE_ROLE_DELIVERY);
            $accessService->revokeModuleAccess($testTakerRole, 'taoDelivery', 'DeliveryServer');

            //grant access to proctor role
            $proctorRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole');
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'Delivery');
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'Diagnostic');
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'Reporting');
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'TestCenter');

            $currentVersion = '0.5';
        }

        if ($currentVersion == '0.5') {
            try {
                $this->getServiceManager()->get(AssessmentResultsService::CONFIG_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new AssessmentResultsService([
                    AssessmentResultsService::OPTION_PRINTABLE_RUBRIC_TAG => 'x-tao-scorereport',
                    AssessmentResultsService::OPTION_PRINT_REPORT_BUTTON => false,
                ]);
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(AssessmentResultsService::CONFIG_ID, $service);
            }
            $currentVersion = '0.6';
        }

        $this->setVersion($currentVersion);

        if ($this->isVersion('0.6')) {

            OntologyUpdater::syncModels();
            //grant access to test site admin role
            $proctorRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterAdministratorRole');
            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'ProctorManager');

            $this->setVersion('0.7');
        }

        if ($this->isVersion('0.7')) {
            try {
                $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new DeliveryMonitoringService(array(DeliveryMonitoringService::OPTION_PERSISTENCE => 'default'));
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(DeliveryMonitoringService::CONFIG_ID, $service);
            }

            include(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'createDeliveryMonitoringTables.php');

            $this->setVersion('0.8.0');
        }

        if ($this->isVersion('0.8.0')) {

            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->attach(TestChangedEvent::EVENT_NAME,
                array('oat\\taoProctoring\\model\\monitorCache\\update\\TestUpdate', 'testStateChange')
            );
            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            $this->setVersion('0.9.0');
        }

        // nothign to do
        if ($this->isVersion('0.9.0')) {
            $this->setVersion('1.0.0');
        }

        if ($this->isVersion('1.0.0')) {

            try {
                $this->getServiceManager()->get(DeliveryAuthorizationService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new DeliveryAuthorizationService();
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(DeliveryAuthorizationService::SERVICE_ID, $service);
            }

            $this->setVersion('1.1.0');
        }

        if ($this->isVersion('1.1.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('1.2.0');
        }

        if ($this->isVersion('1.2.0')) {

            try {
                $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new DeliveryExecutionStateService();
                $service->setServiceManager($this->getServiceManager());

                $this->getServiceManager()->register(DeliveryExecutionStateService::SERVICE_ID, $service);
            }

            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->attach(
                'oat\\taoTests\\models\\event\\TestChangedEvent',
                array('\\oat\\taoProctoring\\helpers\\DeliveryHelper', 'testStateChanged')
            );
            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            $this->setVersion('1.3.0');
        }

        if ($this->isVersion('1.3.0')) {
            $proctoringExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
            $proctoringExtension->setConfig('monitoringUserExtraFields', array());
            $this->setVersion('1.4.0');
        }

        $this->skip('1.4.0', '1.4.1');

        $this->skip('1.4.1', '1.5.0');

        if ($this->isVersion('1.5.0')) {
            try {
                $this->getServiceManager()->get(RdsDeliveryLogService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $action = new RegisterProctoringLog();
                $action->setServiceLocator($this->getServiceManager());
                $action->__invoke(array('default'));
            }
            $this->setVersion('1.6.0');
        }

        if ($this->isVersion('1.6.0')) {

            $settingsScript = new addDiagnosticSettings();
            $settingsScript([]);

            $sqlScript = new createDiagnosticTable();
            $sqlScript([]);

            //Grant access to the overridden controller
            $accessService = \funcAcl_models_classes_AccessService::singleton();

            $taoClientDiagnosticManager = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/generis.rdf#taoClientDiagnosticManager');
            $accessService->grantModuleAccess($taoClientDiagnosticManager, 'taoProctoring', 'DiagnosticChecker');

            $anonymousRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole');
            $accessService->grantModuleAccess($anonymousRole, 'taoProctoring', 'DiagnosticChecker');

            $this->setVersion('1.7.0');
        }

        $this->skip('1.7.0', '1.7.1');

        if ($this->isVersion('1.7.1')) {

            $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            $deliveryExecutionStateService->setOption(DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE, 'PT1H');
            $this->getServiceManager()->register(DeliveryExecutionStateService::SERVICE_ID, $deliveryExecutionStateService);

            $this->setVersion('1.8.0');
        }

        $this->skip('1.8.0', '1.9.0');

        if ($this->isVersion('1.9.0')) {
            $persistence = $this->getServiceManager()->get(PaginatedStorage::SERVICE_ID)->getPersistence();
            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $fromSchema = clone $schema;

            /** @var \Doctrine\DBAL\Schema\Table $tableResults */
            $tableResults = $schema->getTable(DiagnosticStorage::DIAGNOSTIC_TABLE);

            $tableResults->changeColumn(DiagnosticStorage::DIAGNOSTIC_TEST_CENTER, ['notnull' => false]);
            $tableResults->changeColumn(DiagnosticStorage::DIAGNOSTIC_WORKSTATION, ['notnull' => false]);

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

            $this->setVersion('1.9.1');
        }

        if ($this->isVersion('1.9.1')) {
            $assignmentService = new ProctoringAssignmentService();
            $assignmentService->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(ProctoringAssignmentService::CONFIG_ID, $assignmentService);

            $deliveryExt = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
            $deliveryServerConfig = $deliveryExt->getConfig('deliveryServer');
            $deliveryServerOptions = $deliveryServerConfig->getOptions();

            $deliveryServerService = new DeliveryServerService($deliveryServerOptions);
            $deliveryServerService->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(DeliveryServerService::CONFIG_ID, $deliveryServerService);

            $this->setVersion('1.9.2');
        }

        $this->skip('1.9.2','1.12.2');

         if ($this->isVersion('1.12.2')) {
            $persistenceId = $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID)->getOption(DeliveryMonitoringService::OPTION_PERSISTENCE);
            $persistence = \common_persistence_Manager::getPersistence($persistenceId);
            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $fromSchema = clone $schema;
            try {
                $tableData = $schema->getTable(DeliveryMonitoringService::TABLE_NAME);
                $tableData->changeColumn(DeliveryMonitoringService::COLUMN_START_TIME, array('type' => \Doctrine\DBAL\Types\Type::getType('string'), 'notnull' => false, 'length' => 255));
                $tableData->changeColumn(DeliveryMonitoringService::COLUMN_END_TIME, array('type' => \Doctrine\DBAL\Types\Type::getType('string'), 'notnull' => false, 'length' => 255));
            } catch(SchemaException $e) {
                \common_Logger::i('Database Schema already up to date.');
            }
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

            $this->setVersion('1.12.3');
        }

        if ($this->isVersion('1.12.3')) {
            try {
                $this->getServiceManager()->get(TestSessionConnectivityStatusService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new TestSessionConnectivityStatusService();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(TestSessionConnectivityStatusService::SERVICE_ID, $service);
            }

            $this->setVersion('1.13.0');
        }


        if ($this->isVersion('1.13.0')) {

            $this->refreshMonitoringData();

            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->attach('oat\\taoDelivery\\models\\classes\\execution\\event\\DeliveryExecutionState',
                ['oat\\taoProctoring\\model\\monitorCache\\update\\DeliveryExecutionStateUpdate', 'stateChange']
            );


            $eventManager->attach(EligiblityChanged::EVENT_NAME,
                ['oat\\taoProctoring\\model\\monitorCache\\update\\EligiblityUpdate', 'eligiblityChange']
            );

            $eventManager->attach(MetadataModified::class,
                ['oat\\taoProctoring\\model\\monitorCache\\update\\DeliveryUpdate', 'labelChange']
            );

            $eventManager->attach(MetadataModified::class,
                ['oat\\taoProctoring\\model\\monitorCache\\update\\TestTakerUpdate', 'propertyChange']
            );

            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            $this->setVersion('1.14.0');
        }

        $this->skip('1.14.0', '1.14.1');

        if ($this->isVersion('1.14.1')) {
            $this->refreshMonitoringData();
            $this->setVersion('1.14.2');
        }

        if ($this->isVersion('1.14.2') || $this->isVersion('1.14.3')) {
            $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
            $config = $ext->getConfig('execution_service');
            $config = new \oat\taoProctoring\model\execution\DeliveryExecutionService(['implementation' => $config]);
            $ext->setConfig('execution_service', $config);

            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->attach('oat\\taoDelivery\\models\\classes\\execution\\event\\DeliveryExecutionState',
                ['oat\\taoProctoring\\model\\monitorCache\\update\\DeliveryExecutionStateUpdate', 'stateChange']
            );

            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            OntologyUpdater::syncModels();

            $this->refreshMonitoringData();

            $this->setVersion('1.15.0');
        }

        if ($this->isVersion('1.15.0')) {
            $this->refreshMonitoringData();
            $this->setVersion('1.15.1');
        }

        $this->skip('1.15.1', '1.16.2');

        if ($this->isVersion('1.16.2')) {
            OntologyUpdater::syncModels();
            $this->setVersion('1.17.0');
        }

        $this->skip('1.17.0','2.1.0');

        if ($this->isVersion('2.1.0')) {
            $authService = $this->getServiceManager()->get(AuthorizationService::SERVICE_ID);
            if ($authService instanceof AuthorizationAggregator) {
                $authService->unregister(StateValidation::class);
                $authService->addProvider(new ProctorAuthorizationProvider());
                $this->getServiceManager()->register(AuthorizationService::SERVICE_ID, $authService);
            } else {
                throw new \common_exception_Error('Incompatible AuthorizationService "'.get_class($authService).'" found.');
            }
            $this->setVersion('3.0.0');
        }

        $this->skip('3.0.0','3.0.6');

        if($this->isVersion('3.0.6')){
            //grant access to test taker
            $globalManagerRole = new \core_kernel_classes_Resource(INSTANCE_ROLE_GLOBALMANAGER);
            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $accessService->grantModuleAccess($globalManagerRole, 'taoProctoring', 'Irregularity');
            $this->setVersion('3.1.0');
        }

        if($this->isVersion('3.1.0')){
            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->attach('oat\\taoTests\\models\\event\\TestExecutionPausedEvent',
                ['oat\\taoProctoring\\model\\implementation\\DeliveryExecutionStateService', 'catchSessionPause']
            );

            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
            $this->setVersion('3.1.1');
        }

        $this->skip('3.1.1','3.3.1');

        if($this->isVersion('3.3.1')){
            $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
            $config = $ext->getConfig('execution_service');
            $implementation = $config->getImplementation();
            $ext->setConfig('execution_service', $implementation);
            $this->setVersion('3.4.0');
        }

        if ($this->isVersion('3.4.0')) {
            try {
                $this->getServiceManager()->get(TestSessionService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new TestSessionService();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(TestSessionService::SERVICE_ID, $service);
            }
            $this->setVersion('3.4.1');
        }

        $this->skip('3.4.1','3.6.3');

        if ($this->isVersion('3.6.3')) {
            $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
            $deliveryMonitoringService->setOption(
                DeliveryMonitoringService::OPTION_PRIMARY_COLUMNS,
                [
                    DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID,
                    DeliveryMonitoringService::COLUMN_STATUS,
                    DeliveryMonitoringService::COLUMN_CURRENT_ASSESSMENT_ITEM,
                    DeliveryMonitoringService::COLUMN_TEST_TAKER,
                    DeliveryMonitoringService::COLUMN_AUTHORIZED_BY,
                    DeliveryMonitoringService::COLUMN_START_TIME,
                    DeliveryMonitoringService::COLUMN_END_TIME,
                ]
            );
            $this->getServiceManager()->register(DeliveryMonitoringService::CONFIG_ID, $deliveryMonitoringService);
            $this->setVersion('3.6.5');
        }
        $this->skip('3.6.4','3.6.5');

        if ($this->isVersion('3.6.5')) {
            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
            $eventManager->detach(
                'oat\\taoTests\\models\\event\\TestChangedEvent',
                array('\\oat\\taoProctoring\\helpers\\DeliveryHelper', 'testStateChanged')
            );
            $eventManager->attach(
                'oat\\taoQtiTest\\models\\event\\QtiTestStateChangeEvent',
                array('\\oat\\taoProctoring\\helpers\\DeliveryHelper', 'testStateChanged')
            );
            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            $this->setVersion('3.6.6');
        }

        $this->skip('3.6.6', '3.6.18');

        if ($this->isVersion('3.6.18')) {

            $this->getServiceManager()->register(ProctoringTextConverter::SERVICE_ID, new ProctoringTextConverter());

            $proctorRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole');
            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $accessService->grantModuleAccess($proctorRole, 'taoProctoring', 'TextConverter');

            $this->setVersion('3.7.0');
        }

        $this->skip('3.7.0', '3.10.1');

        if ($this->isVersion('3.10.1')) {
            try {
                $this->getServiceManager()->get(TestSessionHistoryService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new TestSessionHistoryService();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(TestSessionHistoryService::SERVICE_ID, $service);
            }
            $this->setVersion('3.11.0');
        }

        if ($this->isVersion('3.11.0')) {
            // register timeHandling option
            try {
                $service = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new DeliveryExecutionStateService([
                    DeliveryExecutionStateService::OPTION_TERMINATION_DELAY_AFTER_PAUSE => 'PT1H',
                    DeliveryExecutionStateService::OPTION_TIME_HANDLING => false,
                ]);
            }
            
            $service->setOption(DeliveryExecutionStateService::OPTION_TIME_HANDLING, false);

            $service->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(DeliveryExecutionStateService::SERVICE_ID, $service);

            // extend the data table
            $persistenceId = $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID)->getOption(DeliveryMonitoringService::OPTION_PERSISTENCE);
            $persistence = \common_persistence_Manager::getPersistence($persistenceId);
            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $fromSchema = clone $schema;
            try {
                $tableData = $schema->getTable(DeliveryMonitoringService::TABLE_NAME);
                $tableData->addColumn(DeliveryMonitoringService::COLUMN_REMAINING_TIME, "string", array("notnull" => false, "length" => 255));
                $tableData->addColumn(DeliveryMonitoringService::COLUMN_EXTRA_TIME, "string", array("notnull" => false, "length" => 255));
                $tableData->addColumn(DeliveryMonitoringService::COLUMN_CONSUMED_EXTRA_TIME, "string", array("notnull" => false, "length" => 255));
            } catch(SchemaException $e) {
                \common_Logger::i('Database Schema already up to date.');
            }
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
            
            $this->setVersion('3.12.0');
        }

        $this->skip('3.12.0', '3.12.1');

        if ($this->isVersion('3.12.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('3.13.0');
        }
        $this->skip('3.13.0', '3.13.7');

        if ($this->isVersion('3.13.7')) {
            try {
                $this->getServiceManager()->get(ReasonCategoryService::SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new ReasonCategoryService();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(ReasonCategoryService::SERVICE_ID, $service);
            }

            $this->setVersion('3.14.0');
        }
    }

    private function refreshMonitoringData()
    {
        \common_Logger::w(__METHOD__ . ' is deprecated! Please use the CLI tool instead! (RefreshMonitoringData)');

    }

}

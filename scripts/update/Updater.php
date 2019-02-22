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

use common_ext_ExtensionUpdater;
use common_persistence_Manager;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\event\MetadataModified;
use oat\tao\model\mvc\DefaultUrlService;
use oat\tao\model\taskQueue\Event\TaskLogArchivedEvent;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\tao\model\user\import\UserCsvImporterFactory;
use oat\tao\model\user\TaoRoles;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoProctoring\controller\DeliverySelection;
use oat\taoProctoring\controller\ExecutionRestService;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\controller\MonitorProctorAdministrator;
use oat\taoProctoring\controller\Tools;
use oat\taoProctoring\model\ActivityMonitoringService;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationDelegator;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationInterface;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoProctoring\model\delivery\DeliverySyncService;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;
use oat\taoProctoring\model\event\DeliveryExecutionFinished;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\execution\ProctoredSectionPauseService;
use oat\taoProctoring\model\FinishDeliveryExecutionsService;
use oat\taoProctoring\model\TerminateDeliveryExecutionsService;
use oat\taoProctoring\model\execution\ProctoringDeliveryDeleteService;
use oat\taoProctoring\model\GuiSettingsService;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\implementation\TestRunnerMessageService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\model\monitorCache\update\TestUpdate;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\model\ProctorServiceDelegator;
use oat\taoProctoring\model\ProctorServiceInterface;
use oat\taoProctoring\model\ReasonCategoryService;
use oat\taoProctoring\model\runner\ProctoringRunnerService;
use oat\taoProctoring\model\service\AbstractIrregularityReport;
use oat\taoProctoring\model\service\IrregularityReport;
use oat\taoProctoring\model\ServiceDelegatorInterface;
use oat\taoProctoring\scripts\install\RegisterBreadcrumbsServices;
use oat\taoProctoring\scripts\install\RegisterGuiSettingsService;
use oat\taoProctoring\scripts\install\RegisterRunnerMessageService;
use oat\taoProctoring\scripts\install\SetUpProctoringUrlService;
use oat\taoQtiTest\models\SectionPauseService;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoProctoring\model\import\ProctorCsvImporter;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoDelivery\model\AttemptService;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\taoProctoring\model\execution\Counter\DeliveryExecutionCounterService;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;

/**
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends common_ext_ExtensionUpdater
{

    /**
     * @param string $initialVersion
     * @return string string
     * @throws \common_Exception
     */
    public function update($initialVersion)
    {
        if ($this->isBetween('0.0.0', '3.11.0')) {
            throw new \common_ext_UpdateException('Please first update to 3.15.0 using taoProctoring 3.15.0');
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

        if ($this->isBetween('3.14.0', '3.16.1')) {
            // ignore eligibility service

            try {
                // drop unused columns
                $monitorService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
                $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
                $persistence = $persistenceManager->getPersistenceById($monitorService->getOption(MonitoringStorage::OPTION_PERSISTENCE));
                $schemaManager = $persistence->getDriver()->getSchemaManager();
                $schema = $schemaManager->createSchema();
                $fromSchema = clone $schema;
                $tableData = $schema->getTable(MonitoringStorage::TABLE_NAME);
                $tableData->dropColumn('remaining_time');
                $tableData->dropColumn('extra_time');
                $tableData->dropColumn('consumed_extra_time');
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            } catch (SchemaException $e) {
                        \common_Logger::i('Database Schema already up to date.');
            }

            // update model
            OntologyUpdater::syncModels();

            // correct event listeners
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(TestChangedEvent::EVENT_NAME,
                array('oat\\taoProctoring\\model\\monitorCache\\update\\TestUpdate', 'testStateChange')
            );
            $eventManager->detach('oat\\taoDelivery\\models\\classes\\execution\\event\\DeliveryExecutionState',
                ['oat\\taoProctoring\\model\\monitorCache\\update\\DeliveryExecutionStateUpdate', 'stateChange']
            );
            $eventManager->detach('oat\\taoProctoring\\model\\event\\EligiblityChanged',
                ['oat\\taoProctoring\\model\\monitorCache\\update\\EligiblityUpdate', 'eligiblityChange']
            );
            $eventManager->detach(\oat\tao\model\event\MetadataModified::class,
                ['oat\\taoProctoring\\model\\monitorCache\\update\\DeliveryUpdate', 'labelChange']
            );
            $eventManager->attach(DeliveryExecutionState::class, [DeliveryMonitoringService::SERVICE_ID, 'executionStateChanged']);
            $eventManager->attach(DeliveryExecutionCreated::class, [DeliveryMonitoringService::SERVICE_ID, 'executionCreated']);
            $eventManager->attach(MetadataModified::class, [DeliveryMonitoringService::SERVICE_ID, 'deliveryLabelChanged']);
            $eventManager->attach(TestChangedEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'testStateChanged']);
            $eventManager->attach(QtiTestStateChangeEvent::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'qtiTestStatusChanged']);
            $eventManager->attach(AuthorizationGranted::EVENT_NAME, [DeliveryMonitoringService::SERVICE_ID, 'deliveryAuthorized']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            // unregister testcenter services
            $this->getServiceManager()->register(AssignmentService::SERVICE_ID, new GroupAssignment());
            $this->getServiceManager()->register(ProctorService::SERVICE_ID, new ProctorService());

            // access rights
            AclProxy::applyRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR, DeliverySelection::class));
            AclProxy::applyRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR, Monitor::class));

            $old = array(
                ['http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',array('oat\\taoProctoring\\controller\\TestCenterManager')],
                ['http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterAdministratorRole',array('oat\\taoProctoring\\controller\\ProctorManager')],
                [ProctorService::ROLE_PROCTOR,'oat\\taoProctoring\\controller\\Delivery'],
                [ProctorService::ROLE_PROCTOR,'oat\\taoProctoring\\controller\\Diagnostic'],
                [ProctorService::ROLE_PROCTOR,'oat\\taoProctoring\\controller\\TestCenter'],
                ['http://www.tao.lu/Ontologies/generis.rdf#taoClientDiagnosticManager','oat\\taoProctoring\\controller\\DiagnosticChecker'],
                [TaoRoles::ANONYMOUS, 'oat\\taoProctoring\\controller\\DiagnosticChecker']
            );
            foreach ($old as $row) {
                list($role, $acl) = $row;
                AclProxy::revokeRule(new AccessRule('grant', $role, $acl));
            }
            $this->setVersion('4.0.0');
        }

        $this->skip('4.0.0', '4.3.0');

        // fix potentially missing roles, moved from 4.1.1
        if ($this->isVersion('4.3.0')) {
            AclProxy::applyRule(new AccessRule('grant',TaoRoles::SYSTEM_ADMINISTRATOR, Tools::class.'@pauseActiveExecutions'));
            $this->setVersion('4.3.1');
        }

        if ($this->isVersion('4.3.1')) {
            /** @var DeliveryMonitoringService $monitoring */
            $action = new UpdateMonitoringTimeValues();
            $action([]);
            $this->setVersion('4.4.0');
        }

        $this->skip('4.4.0', '4.5.2');

        if ($this->isVersion('4.5.2')) {
            /** @var DeliveryMonitoringService $monitoring */
            $action = new UpdateLastConnectivity();
            $action([]);
            $this->setVersion('4.5.3');
        }

        $this->skip('4.5.3', '4.6.2');

         if ($this->isVersion('4.6.2')) {
            $options = $this->getServiceManager()->get('taoProctoring/DeliveryExecutionState')->getOptions();
            $this->getServiceManager()->unregister('taoProctoring/DeliveryExecutionState');
            $service = new DeliveryExecutionStateService($options);
            $this->getServiceManager()->register(StateServiceInterface::SERVICE_ID, $service);
            OntologyUpdater::syncModels();
            $this->setVersion('4.7.0');
        }

        if ($this->isVersion('4.7.0')) {
            /** @var DeliveryExecutionStateService $service */
            $service = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            $service->setOption(DeliveryExecutionStateService::OPTION_CANCELLATION_DELAY, 'PT30M');
            $this->getServiceManager()->register(DeliveryExecutionStateService::SERVICE_ID, $service);
            OntologyUpdater::syncModels();
            $this->setVersion('4.8.0');
        }

        $this->skip('4.8.0', '4.8.1');

        if ($this->isVersion('4.8.1')) {
            AclProxy::applyRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR, \tao_actions_Breadcrumbs::class));

            $this->runExtensionScript(RegisterBreadcrumbsServices::class);

            $this->setVersion('4.9.0');
        }

        if ($this->isVersion('4.9.0')) {
            /** @var DeliveryMonitoringService $monitoring */
            $action = new UpdateLastConnectivity();
            $action([]);
            $this->setVersion('4.9.1');
        }


        $this->skip('4.9.1', '4.10.9');

       if ($this->isVersion('4.10.9')) {

            $this->runExtensionScript(RegisterRunnerMessageService::class);

            $this->setVersion('4.11.0');
       }

        if ($this->isVersion('4.11.0')) {

            $action = new SetUpProctoringUrlService();
            $action->setServiceLocator($this->getServiceManager());
            $action([]);

            $this->setVersion('4.12.0');
        }

        $this->skip('4.12.0', '4.12.2');

        if ($this->isVersion('4.12.2')) {
            $service = new ActivityMonitoringService([
                ActivityMonitoringService::OPTION_ACTIVE_USER_THRESHOLD => 300,
            ]);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $service);
            AclProxy::applyRule(new AccessRule('grant', TaoRoles::OPERATIONAL_ADMINISTRATOR, \oat\taoProctoring\controller\Tools::class));
            $this->setVersion('4.13.0');
        }

        $this->skip('4.13.0', '4.13.1');

        if ($this->isVersion('4.13.1')) {

            $action = new RegisterGuiSettingsService();
            $action->setServiceLocator($this->getServiceManager());
            $action->__invoke([]);

            $this->setVersion('4.14.0');
        }

        if ($this->isVersion('4.14.0')) {

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(TestExecutionPausedEvent::class,
                [DeliveryExecutionStateService::class, 'catchSessionPause']
            );
            $eventManager->attach(TestExecutionPausedEvent::class,
                [DeliveryExecutionStateService::SERVICE_ID, 'catchSessionPause']
            );
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('4.15.0');
        }

        $this->skip('4.15.0', '4.16.0');

        if ($this->isVersion('4.16.0')) {
            $this->getServiceManager()->register(TestTakerAuthorizationService::SERVICE_ID, new TestTakerAuthorizationService());
            $this->setVersion('4.17.0');
        }
        $this->skip('4.17.0', '4.19.1');

        if ($this->isVersion('4.19.1')) {

            /** @var GuiSettingsService $guiService */
            $guiService = $this->getServiceManager()->get(GuiSettingsService::SERVICE_ID);
            $guiService->setOption(GuiSettingsService::PROCTORING_ALLOW_PAUSE, true);
            $this->getServiceManager()->register(GuiSettingsService::SERVICE_ID, $guiService);
            $this->setVersion('4.20.0');
        }

        $this->skip('4.20.0', '5.1.0');

        if ($this->isVersion('5.1.0')) {
            $service = $this->getServiceManager()->get(ActivityMonitoringService::SERVICE_ID);
            $service->setOption(ActivityMonitoringService::OPTION_COMPLETED_ASSESSMENTS_AUTO_REFRESH, 30);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $service);
            $this->setVersion('5.1.1');
        }

        $this->skip('5.1.1', '5.2.1');

        if ($this->isVersion('5.2.1')) {
            $service = $this->getServiceManager()->get(ActivityMonitoringService::SERVICE_ID);
            $service->setOption(ActivityMonitoringService::OPTION_ASSESSMENT_ACTIVITY_AUTO_REFRESH, 60);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $service);
            $this->setVersion('5.3.0');
        }

        $this->skip('5.3.0', '5.9.0');

        if ($this->isVersion('5.9.0') || $this->isVersion('5.9.1')) {
            $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
            $urlService->setRoute('ProctoringDeliveryServer', [
                    'ext' => 'taoProctoring',
                    'controller' => 'DeliveryServer',
                    'action' => 'index',
                ]
            );
            $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $urlService);
            $this->setVersion('5.10.0');
        }

        $this->skip('5.10.0', '5.10.3');

        if ($this->isVersion('5.10.3')) {
            OntologyUpdater::syncModels();
            $this->setVersion('5.11.0');
        }

        if ($this->isVersion('5.11.0')) {
            $persistence = $this->getServiceManager()
                ->get(common_persistence_Manager::SERVICE_ID)
                ->getPersistenceById('default');

            $persistence->getPlatForm()->getQueryBuilder()
                ->update('kv_delivery_monitoring')
                ->set('monitoring_value', "REPLACE(monitoring_value, 's', '')")
                ->where('monitoring_key = ? and monitoring_value is not null')
                ->setParameters(['remaining_time'])
                ->execute();

            $this->setVersion('5.12.0');
        }

        $this->skip('5.12.0', '5.12.2');

        if ($this->isVersion('5.12.2')) {
            $this->getServiceManager()->register(
                DeliveryExecutionManagerService::SERVICE_ID,
                new DeliveryExecutionManagerService()
            );
            $this->setVersion('5.13.0');
        }

        if ($this->isVersion('5.13.0')) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach('oat\\taoProctoring\\model\\event\\DeliveryExecutionFinished', [LoggerService::class, 'logEvent']);
            $eventManager->attach('oat\\taoProctoring\\model\\event\\DeliveryExecutionFinished', [LoggerService::class, 'logEvent']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('5.13.1');
        }

        $this->skip('5.13.1', '5.15.1');

        if ($this->isVersion('5.15.1')) {
            $this->getServiceManager()->register(SectionPauseService::SERVICE_ID, new ProctoredSectionPauseService());
            $this->setVersion('5.16.0');
        }

        $this->skip('5.16.0', '5.16.4');

        if ($this->isVersion('5.16.4')) {
            $proctorService = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
            $config = $proctorService->getOptions();
            $config[ProctorService::PROCTORED_BY_DEFAULT] = true;

            $service = new ProctorService($config);
            $service->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(ProctorService::SERVICE_ID, $service);

            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(DeliveryCreatedEvent::class, [ProctorService::SERVICE_ID, 'listenCreateDeliveryEvent']);
            $eventManager->attach(DeliveryUpdatedEvent::class, [ProctorService::SERVICE_ID, 'listenUpdateDeliveryEvent']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('5.16.5');
        }

        if ($this->isVersion('5.16.5')) {
            OntologyUpdater::syncModels();
            $this->setVersion('5.16.6');
        }
        $this->skip('5.16.6', '5.16.9');

         if ($this->isVersion('5.16.9')) {
            $this->getServiceManager()->register(AbstractIrregularityReport::SERVICE_ID, new IrregularityReport());
            $this->setVersion('5.17.0');
         }

        $this->skip('5.17.0', '5.18.1');

        if ($this->isVersion('5.18.1')) {

            $proctorService = $this->getServiceManager()->get(ProctorServiceInterface::SERVICE_ID);
            $authService = $this->getServiceManager()->get(TestTakerAuthorizationService::SERVICE_ID);
            if ($proctorService->hasOption(TestTakerAuthorizationService::PROCTORED_BY_DEFAULT)) {
                $authService->setOption(
                    TestTakerAuthorizationService::PROCTORED_BY_DEFAULT,
                    $proctorService->getOption(TestTakerAuthorizationService::PROCTORED_BY_DEFAULT)
                );
                $this->getServiceManager()->register(TestTakerAuthorizationService::SERVICE_ID, $authService);
            }

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(DeliveryCreatedEvent::class, [ProctorService::SERVICE_ID, 'listenCreateDeliveryEvent']);
            $eventManager->detach(DeliveryUpdatedEvent::class, [ProctorService::SERVICE_ID, 'listenUpdateDeliveryEvent']);
            $eventManager->attach(DeliveryCreatedEvent::class, [TestTakerAuthorizationService::SERVICE_ID, 'onDeliveryCreated']);
            $eventManager->attach(DeliveryUpdatedEvent::class, [TestTakerAuthorizationService::SERVICE_ID, 'onDeliveryUpdated']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $service = $this->getServiceManager()->get(ProctorServiceInterface::SERVICE_ID);
            if (!is_a($service, ProctorServiceDelegator::class)) {
                $delegator = new ProctorServiceDelegator([ProctorServiceDelegator::PROCTOR_SERVICE_HANDLERS => [$service]]);
                $this->getServiceManager()->register(ProctorServiceInterface::SERVICE_ID, $delegator);
            }
            $this->setVersion('6.0.0');
        }

        $this->skip('6.0.0', '6.1.2');

        if ($this->isVersion('6.1.2')) {

            $authService = $this->getServiceManager()->get(TestTakerAuthorizationInterface::SERVICE_ID);
            // register DeliverySyncService
            $oldDefault = $authService->hasOption(DeliverySyncService::PROCTORED_BY_DEFAULT)
                ? $authService->getOption(DeliverySyncService::PROCTORED_BY_DEFAULT)
                : false;
            $syncService = new DeliverySyncService();
            $this->getServiceManager()->register(DeliverySyncService::SERVICE_ID, $syncService->setProctoredByDefault($oldDefault));

            // wrap auth service
            if (!is_a($authService, TestTakerAuthorizationDelegator::class)) {
                $delegator = new TestTakerAuthorizationDelegator ([
                    ServiceDelegatorInterface::SERVICE_HANDLERS => [
                        new TestTakerAuthorizationService(),
                    ],
                ]);
                $this->getServiceManager()->register(TestTakerAuthorizationInterface::SERVICE_ID, $delegator);
            }

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(DeliveryCreatedEvent::class, [TestTakerAuthorizationService::SERVICE_ID, 'onDeliveryCreated']);
            $eventManager->detach(DeliveryUpdatedEvent::class, [TestTakerAuthorizationService::SERVICE_ID, 'onDeliveryUpdated']);
            $eventManager->attach(DeliveryCreatedEvent::class, [DeliverySyncService::SERVICE_ID, 'onDeliveryCreated']);
            $eventManager->attach(DeliveryUpdatedEvent::class, [DeliverySyncService::SERVICE_ID, 'onDeliveryUpdated']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('7.0.0');
        }

        $this->skip('7.0.0', '7.2.1');

        if ($this->isVersion('7.2.1')) {
            $runnerService = new ProctoringRunnerService();
            $runnerService->setServiceManager($this->getServiceManager());
            $this->getServiceManager()->register(ProctoringRunnerService::SERVICE_ID, $runnerService);
            $this->setVersion('7.3.0');
        }

        $this->skip('7.3.0', '7.3.4');

        if ($this->isVersion('7.3.4')) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(TestChangedEvent::EVENT_NAME, [TestUpdate::class, 'testStateChange']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('7.4.0');
        }

        $this->skip('7.4.0', '7.8.6');

        if ($this->isVersion('7.8.6')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR_ADMINISTRATOR, MonitorProctorAdministrator::class));

            $this->setVersion('7.9.0');
        }

        $this->skip('7.9.0', '7.11.0');

        if ($this->isVersion('7.11.0')) {
            /** @var TestRunnerMessageService $testRunnerMessageService */
            $testRunnerMessageService = $this->getServiceManager()->get(TestRunnerMessageService::SERVICE_ID);
            $testRunnerMessageService->setOption(
                TestRunnerMessageService::PROCTOR_ROLES_OPTION,
                [
                    ProctorService::ROLE_PROCTOR,
                ]
            );
            $this->getServiceManager()->register(TestRunnerMessageService::SERVICE_ID, $testRunnerMessageService);

            $this->setVersion('7.11.1');
        }

        $this->skip('7.11.1', '7.11.6');

        if ($this->isVersion('7.11.6')) {
            /** @var ActivityMonitoringService $service */
            $service = $this->getServiceManager()->get(ActivityMonitoringService::SERVICE_ID);
            $options = array_merge($service->getOptions(), [ActivityMonitoringService::OPTION_USER_ACTIVITY_WIDGETS => [],]);
            $service->setOptions($options);
            $this->getServiceManager()->register(ActivityMonitoringService::SERVICE_ID, $service);
            $this->setVersion('7.12.0');
        }

        $this->skip('7.12.0', '8.1.0');

        if ($this->isVersion('8.1.0')) {
            $monitorService = $this->getServiceManager()->get(MonitoringStorage::SERVICE_ID);
            $monitorService->setOption(MonitoringStorage::OPTION_USE_UPDATE_MULTIPLE, false);

            $this->getServiceManager()->register(MonitoringStorage::SERVICE_ID, $monitorService);

            $this->setVersion('8.2.0');
        }

        $this->skip('8.2.0', '8.4.0');

        if ($this->isVersion('8.4.0')) {
            $proctoringExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
            $proctoringExtension->setConfig('monitoringUserExtraFieldsSettings', []);
            $this->setVersion('8.5.0');
        }

        if ($this->isVersion('8.5.0')) {
            try {
                // drop unused columns
                $monitorService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
                $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
                $persistence = $persistenceManager->getPersistenceById($monitorService->getOption(MonitoringStorage::OPTION_PERSISTENCE));
                $schemaManager = $persistence->getDriver()->getSchemaManager();
                $schema = $schemaManager->createSchema();
                $fromSchema = clone $schema;
                $tableData = $schema->getTable(MonitoringStorage::TABLE_NAME);
                $tableData->dropColumn(MonitoringStorage::DELIVERY_NAME);
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            } catch (SchemaException $e) {
                \common_Logger::i('Database Schema already up to date.');
            }


            $monitorService = $this->getServiceManager()->get(MonitoringStorage::SERVICE_ID);
            $monitorService->setOption(MonitoringStorage::OPTION_CACHE_SIZE, 2000);

            $this->getServiceManager()->register(MonitoringStorage::SERVICE_ID, $monitorService);



            $this->setVersion('8.5.1');

        }

        if ($this->isVersion('8.5.1')) {
            try {
                $monitorService = $this->getServiceManager()->get(DeliveryMonitoringService::SERVICE_ID);
                $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
                $persistence = $persistenceManager->getPersistenceById($monitorService->getOption(MonitoringStorage::OPTION_PERSISTENCE));
                $schemaManager = $persistence->getDriver()->getSchemaManager();
                $schema = $schemaManager->createSchema();
                $fromSchema = clone $schema;
                $tableData = $schema->getTable(MonitoringStorage::TABLE_NAME);
                $tableData->addColumn(MonitoringStorage::DELIVERY_ID, "text", array("notnull" => false));
                $tableData->addColumn(MonitoringStorage::DELIVERY_NAME, "text", array("notnull" => false));
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            } catch (SchemaException $e) {
                \common_Logger::i('Database Schema already up to date.');
            }
            $this->setVersion('8.5.2');
        }

        $this->skip('8.5.2', '8.6.2');

        if ($this->isVersion('8.6.2')) {
            /** @var UserCsvImporterFactory $importerFactory */
            $importerFactory = $this->getServiceManager()->get(UserCsvImporterFactory::SERVICE_ID);
            $typeOptions = $importerFactory->getOption(UserCsvImporterFactory::OPTION_MAPPERS);
            $typeOptions[ProctorCsvImporter::USER_IMPORTER_TYPE] = array(
                UserCsvImporterFactory::OPTION_MAPPERS_IMPORTER => new ProctorCsvImporter()
            );
            $importerFactory->setOption(UserCsvImporterFactory::OPTION_MAPPERS, $typeOptions);
            $this->getServiceManager()->register(UserCsvImporterFactory::SERVICE_ID, $importerFactory);

            $this->setVersion('8.7.0');
        }

        $this->skip('8.7.0', '8.7.1');

        if ($this->isVersion('8.7.1')) {
            $assessmentResultsService = $this->getServiceManager()->get(AssessmentResultsService::SERVICE_ID);
            $assessmentResultsService->setOption(
                AssessmentResultsService::OPTION_SCORE_URL,
                array(
                    'extension' => 'taoProctoring',
                    'controller' => 'Reporting',
                    'action' => 'printReport'
                )
            );
            $this->getServiceManager()->register(AssessmentResultsService::SERVICE_ID, $assessmentResultsService);
            $this->setVersion('8.8.0');
        }

        if ($this->isVersion('8.8.0')) {
            $this->getServiceManager()->register(AttemptServiceInterface::SERVICE_ID, new AttemptService([]));
            $this->setVersion('8.9.0');
        }

        $this->skip('8.9.0', '8.9.2');

        if ($this->isVersion('8.9.2')) {
            $attemptService = $this->safeLoadService(AttemptServiceInterface::SERVICE_ID);
            if (!$attemptService instanceof AttemptServiceInterface) {
                $attemptService = new AttemptService([]);
            }
            $statesToExclude = $attemptService->getStatesToExclude();
            $statesToExclude[] = ProctoredDeliveryExecution::STATE_CANCELED;
            $attemptService->setStatesToExclude($statesToExclude);
            $this->getServiceManager()->register(AttemptServiceInterface::SERVICE_ID, $attemptService);
            $this->setVersion('8.10.0');
        }

        $this->skip('8.10.0', '8.10.1');

        if ($this->isVersion('8.10.1')) {
            $terminateDEService = new TerminateDeliveryExecutionsService([
                TerminateDeliveryExecutionsService::OPTION_TTL_AS_ACTIVE => 'PT6H',
                TerminateDeliveryExecutionsService::OPTION_USE_DELIVERY_END_TIME => false,
            ]);

            $this->getServiceManager()->register(TerminateDeliveryExecutionsService::SERVICE_ID, $terminateDEService);

            $this->setVersion('8.10.2');
        }

        $this->skip('8.10.2', '8.11.0');

        if ($this->isVersion('8.11.0')) {
            $this->getServiceManager()->register(
                DeliveryExecutionCounterInterface::SERVICE_ID,
                new DeliveryExecutionCounterService()
            );
            $this->setVersion('8.12.0');
        }

        $this->skip('8.12.0', '8.13.0');

        if ($this->isVersion('8.13.0')) {
            $terminateDEService = new TerminateDeliveryExecutionsService([
                TerminateDeliveryExecutionsService::OPTION_TTL_AS_ACTIVE => 'PT6H',
                TerminateDeliveryExecutionsService::OPTION_USE_DELIVERY_END_TIME => false,
            ]);

            $this->getServiceManager()->register(TerminateDeliveryExecutionsService::SERVICE_ID, $terminateDEService);

            $this->setVersion('8.13.1');
        }

        $this->skip('8.13.1', '8.13.3');

        if ($this->isVersion('8.13.3')){
            /** @var DeliveryDeleteService $deleteDelivery */
            $deleteDelivery        = $this->getServiceManager()->get(DeliveryDeleteService::SERVICE_ID);
            $proctorDeleteDelivery = new ProctoringDeliveryDeleteService($deleteDelivery->getOptions());

            $this->getServiceManager()->register(DeliveryDeleteService::SERVICE_ID, $proctorDeleteDelivery);

            $this->setVersion('8.14.0');
        }

        $this->skip('8.14.0', '9.2.2');

        if ($this->isVersion('9.2.2')) {

            /** @var RdsDeliveryLogService $deliveryLog */
            $deliveryLog = $this->getServiceManager()->get(RdsDeliveryLogService::SERVICE_ID);

            $deliveryLog->setOption(RdsDeliveryLogService::OPTION_FIELDS, [
                RdsDeliveryLogService::EVENT_ID,
                RdsDeliveryLogService::CREATED_BY,
                RdsDeliveryLogService::DELIVERY_EXECUTION_ID,
            ]);

            $this->getServiceManager()->register(RdsDeliveryLogService::SERVICE_ID, $deliveryLog);

            $this->setVersion('9.3.0');
        }

        $this->skip('9.3.0', '10.1.0');

        if ($this->isVersion('10.1.0')) {
            $finishDEService = new FinishDeliveryExecutionsService([
                FinishDeliveryExecutionsService::OPTION_TTL_AS_ACTIVE => 'PT6H',
                FinishDeliveryExecutionsService::OPTION_USE_DELIVERY_END_TIME => false,
            ]);
            $this->getServiceManager()->register(FinishDeliveryExecutionsService::SERVICE_ID, $finishDEService);
            $this->setVersion('10.2.0');
        }

        $this->skip('10.2.0', '11.0.0');

        if ($this->isVersion('10.2.4')){
            AclProxy::applyRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR, 'oat\\taoProctoring\\controller\\ExecutionRestService'));
            $this->setVersion('10.3.0');
        }

        if ($this->isVersion('10.3.0')) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(TestChangedEvent::EVENT_NAME, [TestUpdate::class, 'testStateChange']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('10.3.1');
        }

        $this->skip('10.3.1', '11.0.0');

        if ($this->isVersion('11.0.0')) {
            AclProxy::revokeRule(new AccessRule('grant', ProctorService::ROLE_PROCTOR, 'oat\\taoProctoring\\controller\\ExecutionRestService'));
            $this->setVersion('12.0.0');
        }


        $this->skip('12.0.0', '12.3.0');

        if ($this->isVersion('12.3.0')) {

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(DeliveryExecutionFinished::class, ['oat\\taoEventLog\\model\\LoggerService', 'logEvent']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('12.3.1');
        }

        $this->skip('12.3.1', '12.3.2');

        if ($this->isVersion('12.3.2')) {
            $extensionManager = \common_ext_ExtensionsManager::singleton();
            if (!$extensionManager->isInstalled('taoTestCenter') || !$extensionManager->isEnabled('taoTestCenter')) {
                /** @var DefaultUrlService $urlService */
                $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
                $proctoringHomeRoute = $urlService->getRoute('ProctoringHome');

                $dumbRoute = [
                    'ext' => 'taoProctoring',
                    'controller' => 'TestCenter',
                    'action' => 'index'
                ];

                if ($proctoringHomeRoute == $dumbRoute) {
                    $urlService->setRoute('ProctoringHome', [
                            'ext' => 'tao',
                            'controller' => 'Main',
                            'action' => 'entry',
                        ]
                    );
                }
                $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $urlService);
            }

            $this->setVersion('12.3.3');
        }

        $this->skip('12.3.3', '12.3.4');

        if ($this->isVersion('12.3.4')) {
            /** @var TaskLogInterface|ConfigurableService $taskLogService */
            $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);
            $taskLogService->linkTaskToCategory(AbstractIrregularityReport::class, TaskLogInterface::CATEGORY_EXPORT);
            $this->getServiceManager()->register(TaskLogInterface::SERVICE_ID, $taskLogService);

            $this->setVersion('12.4.0');
        }

        $this->skip('12.4.0', '12.5.2');

        if ($this->isVersion('12.5.2')) {
            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(DeliveryExecutionFinished::class, [LoggerService::class, 'logEvent']);
            $eventManager->attach(DeliveryExecutionFinished::class, [LoggerService::class, 'logEvent']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('12.5.3');
        }

        $this->skip('12.5.3', '12.6.1');
    }
}

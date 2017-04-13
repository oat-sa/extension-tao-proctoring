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
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\event\MetadataModified;
use oat\tao\model\user\TaoRoles;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoProctoring\controller\DeliverySelection;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\controller\Tools;
use oat\taoProctoring\model\authorization\AuthorizationGranted;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\model\ReasonCategoryService;
use oat\taoProctoring\scripts\install\RegisterBreadcrumbsServices;
use oat\taoProctoring\scripts\install\RegisterGuiSettingsService;
use oat\taoProctoring\scripts\install\SetUpProctoringUrlService;
use oat\taoProctoring\scripts\install\RegisterRunnerMessageService;
use oat\taoQtiTest\models\event\QtiTestStateChangeEvent;
use oat\taoTests\models\event\TestChangedEvent;
use oat\taoProctoring\model\ActivityMonitoringService;
use oat\taoTests\models\event\TestExecutionPausedEvent;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;

/**
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends common_ext_ExtensionUpdater
{

    /**
     * @param string $initialVersion
     * @return string string
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
    }
}

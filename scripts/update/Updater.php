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
use oat\tao\model\entryPoint\EntryPointService;
use oat\taoProctoring\model\implementation\DeliveryService;
use oat\taoProctoring\model\entrypoint\ProctoringDeliveryServer;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoProctoring\model\AssessmentResultsService;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\oatbox\event\EventManager;
use oat\taoTests\models\event\TestChangedEvent;

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
    }

}
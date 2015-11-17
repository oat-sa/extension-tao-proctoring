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
use oat\taoProctoring\model\implementation\TestCenterService;
use oat\taoProctoring\model\entrypoint\ProctoringDeliveryServer;

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
            $service = new TestCenterService();
            $ext->setConfig('testCenter', $service);
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

        return $currentVersion;
    }

}
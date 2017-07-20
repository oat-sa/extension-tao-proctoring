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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Joel Bout, <joel@taotesting.com>
 */

namespace oat\taoProctoring\scripts\uninstall;


use oat\oatbox\extension\UninstallAction;
use oat\tao\model\entryPoint\EntryPointService;
use oat\taoDelivery\model\entrypoint\FrontOfficeEntryPoint;
use oat\taoDelivery\model\execution\DeliveryServerService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoDelivery\model\authorization\strategy\StateValidation;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoProctoring\model\ActivityMonitoringService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoDelivery\model\execution\StateService;
use oat\taoProctoring\model\breadcrumbs\DeliverySelectionService;
use oat\taoProctoring\model\breadcrumbs\MonitorService;
use oat\taoProctoring\model\breadcrumbs\ReportingService;
use oat\taoProctoring\model\ReasonCategoryService;
use oat\taoProctoring\model\implementation\TestRunnerMessageService;
use oat\taoProctoring\model\GuiSettingsService;
use oat\tao\model\mvc\DefaultUrlService;
use oat\taoQtiTest\models\runner\QtiRunnerMessageService;

class RestoreServices extends UninstallAction
{
    public function __invoke($params) {

        // checks
        $authService = $this->getServiceManager()->get(AuthorizationService::SERVICE_ID);
        if (!$authService instanceof AuthorizationAggregator) {
            throw new \common_exception_Error('Incompatible AuthorizationService "'.get_class($authService).'" found.');
        }

        // restore entry points
        $entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);
        $entryPointService->removeEntryPoint('proctoring');
        $entryPointService->overrideEntryPoint('deliveryServer', new FrontOfficeEntryPoint());
        $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $entryPointService);

        // remove unneeded services
        $this->unregisterService(DeliveryMonitoringService::SERVICE_ID);
        $this->unregisterService(RdsDeliveryLogService::SERVICE_ID);
        $this->unregisterService(ActivityMonitoringService::SERVICE_ID);
        $this->unregisterService(ReasonCategoryService::SERVICE_ID);
        $this->unregisterService(GuiSettingsService::SERVICE_ID);

        // remove breadcrumbs
        $this->unregisterService(DeliverySelectionService::SERVICE_ID);
        $this->unregisterService(MonitorService::SERVICE_ID);
        $this->unregisterService(ReportingService::SERVICE_ID);

        // restore delivery server
        $deliveryConfig = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID)->getOptions();
        $this->getServiceManager()->register(DeliveryServerService::SERVICE_ID, new DeliveryServerService($deliveryConfig));

        // restore authorisation provider
        $authService->unregister(ProctorAuthorizationProvider::class);
        $authService->addProvider(new StateValidation());
        $this->getServiceManager()->register(AuthorizationService::SERVICE_ID, $authService);

        // restore state service
        $this->getServiceManager()->register(StateServiceInterface::SERVICE_ID, new StateService());
        
        // Restore QTI runner
        $this->getServiceManager()->register(TestRunnerMessageService::SERVICE_ID, new QtiRunnerMessageService());
        
        // get rid of custom URLs
        $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
        $options = $urlService->getOptions();
        unset($options['ProctoringHome']);
        unset($options['ProctoringLogout']);
        $urlService->setOptions($options);
        $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $urlService);
    }
}

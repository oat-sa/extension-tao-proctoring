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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */

namespace oat\taoProctoring\scripts\install;


use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\model\breadcrumbs\DeliverySelectionService;
use oat\taoProctoring\model\breadcrumbs\MonitorService;
use oat\taoProctoring\model\breadcrumbs\ReportingService;

class RegisterBreadcrumbsServices extends InstallAction
{
    
    public function __invoke($params)
    {
        $breadcrumbsDeliveries = new DeliverySelectionService();
        $this->registerService(DeliverySelectionService::SERVICE_ID, $breadcrumbsDeliveries);

        $breadcrumbsMonitor = new MonitorService();
        $this->registerService(MonitorService::SERVICE_ID, $breadcrumbsMonitor);

        $breadcrumbsReporting = new ReportingService();
        $this->registerService(ReportingService::SERVICE_ID, $breadcrumbsReporting);
    }

}

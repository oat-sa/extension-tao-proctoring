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
* Copyright (c) 2016 (original work) Open Assessment Technologies SA;
*
*
*/

namespace oat\taoProctoring\scripts\install;

use oat\tao\model\entryPoint\EntryPointService;
use oat\taoProctoring\model\entrypoint\ProctoringDeliveryServer;
use oat\taoProctoring\model\entrypoint\ProctoringEntryPoint;

/**
 * Class RegisterAssignmentService
 * @package oat\taoProctoring\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterProctoringEntryPoint extends \common_ext_action_InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);
        $entryPointService->registerEntryPoint(new ProctoringEntryPoint());
        $entryPointService->overrideEntryPoint('deliveryServer', new ProctoringDeliveryServer());
        $this->registerService(EntryPointService::SERVICE_ID, $entryPointService);
    }
}
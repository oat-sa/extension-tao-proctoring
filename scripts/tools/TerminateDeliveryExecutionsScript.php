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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoProctoring\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use oat\taoProctoring\model\TerminateDeliveryExecutionsService;

class TerminateDeliveryExecutionsScript extends ScriptAction
{
    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'TAO Delivery - Terminate Delivery Executions';
    }

    /**
     * Run Script.
     *
     * Run the userland script. Implementers will use this method
     * to implement the main logic of the script.
     *
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    protected function run()
    {
        /** @var TerminateDeliveryExecutionsService $terminateDeService */
        $terminateDeService = $this->getServiceLocator()->get(TerminateDeliveryExecutionsService::SERVICE_ID);

        return $terminateDeService->execute();
    }
}
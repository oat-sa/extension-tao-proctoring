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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoProctoring\scripts\tools;

use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\model\Command\ProctorCommandManagerService;
use oat\taoProctoring\model\Command\ProctorCommandStorageKV;
use common_report_Report as Report;

/**
 * Run example: `sudo php index.php 'oat\taoProctoring\scripts\tools\SetupProctorCommandWithStorageExecution'
 */

class SetupProctorCommandWithStorageExecution extends InstallAction
{
    public function __invoke($params)
    {
        $proctorManager = new ProctorCommandManagerService([
            ProctorCommandManagerService::OPTION_SHOULD_EXECUTE_LATER => true
        ]);

        $this->registerService(ProctorCommandManagerService::SERVICE_ID, $proctorManager);

        $proctorCommandStorage = new ProctorCommandStorageKV([
            ProctorCommandStorageKV::OPTION_PERSISTENCE_ID => 'serviceState'
        ]);

        $this->registerService(ProctorCommandStorageKV::SERVICE_ID, $proctorCommandStorage);

        return Report::createSuccess('ProctorCommandWithStorageExecution setup with success.');
    }
}
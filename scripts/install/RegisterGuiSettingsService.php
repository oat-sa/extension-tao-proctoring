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
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\scripts\install;


use oat\oatbox\extension\InstallAction;
use oat\taoProctoring\model\GuiSettingsService;

class RegisterGuiSettingsService extends InstallAction
{

    /**
     * Configure and register the GuiSettingsService
     */
    public function __invoke($params)
    {
        $service = new GuiSettingsService([
            /**
             * Allow refresh button
             */
            GuiSettingsService::PROCTORING_REFRESH_BUTTON => true,

            /**
             * Without Auto Refresh for the content
             */
            GuiSettingsService::PROCTORING_AUTO_REFRESH => 0,

            GuiSettingsService::PROCTORING_ALLOW_PAUSE => true
        ]);

        $service->setServiceManager($this->getServiceManager());
        $this->getServiceManager()->register(GuiSettingsService::SERVICE_ID, $service);
    }
}

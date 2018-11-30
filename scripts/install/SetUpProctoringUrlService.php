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
use oat\tao\model\mvc\DefaultUrlService;

class SetUpProctoringUrlService extends InstallAction
{
    public function __invoke($params) {
        $urlService = $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID);
        $urlService->setRoute('ProctoringHome', [
                'ext' => 'tao',
                'controller' => 'Main',
                'action' => 'entry',
            ]
        );
        $urlService->setRoute('ProctoringLogout', [
                'ext' => 'tao',
                'controller' => 'Main',
                'action' => 'logout',
            ]
        );
        $urlService->setRoute('ProctoringDeliveryServer', [
                'ext' => 'taoProctoring',
                'controller' => 'DeliveryServer',
                'action' => 'index',
            ]
        );
        $this->getServiceManager()->register(DefaultUrlService::SERVICE_ID, $urlService);
    }
}

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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\requirements\RequirementsService;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoDelivery\model\authorization\strategy\AuthorizationAggregator;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoDelivery\model\authorization\strategy\StateValidation;


/**
 * Installation action that register the requirements service.
 *
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 */
class RegisterAuthProvider extends \common_ext_action_InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $authService = $this->getServiceManager()->get(AuthorizationService::SERVICE_ID);
        if ($authService instanceof AuthorizationAggregator) {
            $authService->unregister(StateValidation::class);
            $authService->addProvider(new ProctorAuthorizationProvider());
            $this->registerService(AuthorizationService::SERVICE_ID, $authService);
        } else {
            throw new \common_exception_Error('Incompatible AuthorizationService "'.get_class($authService).'" found.');
        }
    }
}

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
 */
namespace oat\taoProctoring\model\authorization;

use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoDelivery\model\authorization\DeliveryAuthorizationProvider;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\EligibilityService;

/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorDeliveryAuthorizationService extends ConfigurableService  implements AuthorizationService
{


    /**
     * Returns the the authorization provider.
     *
     * This implementation selects the provider based on the Eligility's property canByPassProctor.
     * If the propery is true, then we don't need proctor authorization. All others cases goes into the
     * proctoring authoriaztion.
     *
     * @param DeliveryExecution $deliveryExecution the delivery execution to authorize
     * @param User $user the current user
     * @return AuthorizationProvider a new provider for this execution
     */
    public function getAuthorizationProvider(DeliveryExecution $deliveryExecution, User $user)
    {
        $eligibilityService = EligibilityService::singleton();

        if( $deliveryExecution->exists()){
            $testCenter = $eligibilityService->getTestCenter($deliveryExecution->getDelivery(), $user);

            if(!is_null($testCenter)){
                $eligibility = $eligibilityService->getEligibility($testCenter, $deliveryExecution->getDelivery());

                if($eligibilityService->canByPassProctor($eligibility)){
                    return new DeliveryAuthorizationProvider();
                }
            }
        }
        return new ProctorAuthorizationProvider($deliveryExecution);
    }
}

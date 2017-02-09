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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoProctoring\helpers\DeliveryHelper;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;

/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProvider extends ConfigurableService implements AuthorizationProvider
{
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        // always allow start
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyResumeAuthorization()
     */
    public function verifyResumeAuthorization(DeliveryExecution $deliveryExecution, User $user)
    {
        $state = $deliveryExecution->getState()->getUri();

        if (in_array($state, [ProctoredDeliveryExecution::STATE_FINISHED, ProctoredDeliveryExecution::STATE_TERMINATED])) {
            throw new UnAuthorizedException(
                _url('index', 'DeliveryServer', 'taoProctoring'),
                'Terminated/Finished delivery cannot be resumed'
            );
        }

        if($user instanceof GuestTestUser && $this->hasDeliveryGuestAccess($deliveryExecution->getDelivery())){
            $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            $deliveryExecutionStateService->waitExecution($deliveryExecution);
            DeliveryHelper::authoriseExecutions([$deliveryExecution]);
        }

        if ($state !== ProctoredDeliveryExecution::STATE_AUTHORIZED) {
            $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'taoProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
            throw new UnAuthorizedException($errorPage, 'Proctor authorization missing');
        }
    }

    protected function hasDeliveryGuestAccess(\core_kernel_classes_Resource $delivery )
    {
        $returnValue = false;

        $properties = $delivery->getPropertiesValues(array(
            new \core_kernel_classes_Property(TAO_DELIVERY_ACCESS_SETTINGS_PROP),
        ));
        $propAccessSettings = current($properties[TAO_DELIVERY_ACCESS_SETTINGS_PROP]);
        $accessSetting = (!(is_object($propAccessSettings)) or ($propAccessSettings=="")) ? null : $propAccessSettings->getUri();

        if( !is_null($accessSetting) ){
            $returnValue = ($accessSetting === DELIVERY_GUEST_ACCESS);
        }

        return $returnValue;
    }
}

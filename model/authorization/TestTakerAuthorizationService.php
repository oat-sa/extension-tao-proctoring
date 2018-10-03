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

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\DelegatedServiceHandler;
use oat\taoProctoring\model\delivery\DeliverySyncService;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoProctoring\model\ProctorService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoTestRunnerPlugins\model\event\DeliveryTypeEvent;

/**
 * Manage the Delivery authorization.
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class TestTakerAuthorizationService extends ConfigurableService implements TestTakerAuthorizationInterface, DelegatedServiceHandler
{
    use OntologyAwareTrait;

    /**
     * @deprecated moved to \oat\taoProctoring\model\delivery\DeliverySyncService::PROCTORED_BY_DEFAULT
     */
    const PROCTORED_BY_DEFAULT = 'proctored_by_default';

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyStartAuthorization()
     * @param $deliveryId
     * @param User $user
     */
    public function verifyStartAuthorization($deliveryId, User $user)
    {
        // always allow start
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\authorization\AuthorizationProvider::verifyResumeAuthorization()
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param User $user
     * @throws UnAuthorizedException
     */
    public function verifyResumeAuthorization(DeliveryExecutionInterface $deliveryExecution, User $user)
    {
        $state = $deliveryExecution->getState()->getUri();
        if (in_array($state, [
            ProctoredDeliveryExecution::STATE_FINISHED,
            ProctoredDeliveryExecution::STATE_CANCELED,
            ProctoredDeliveryExecution::STATE_TERMINATED])
        ) {
            throw new UnAuthorizedException(
                _url('index', 'DeliveryServer', 'taoProctoring'),
                'Terminated/Finished delivery execution "'.$deliveryExecution->getIdentifier().'" cannot be resumed'
            );
        }
        if ($this->isProctored($deliveryExecution->getDelivery()->getUri(), $user) && $state !== ProctoredDeliveryExecution::STATE_AUTHORIZED) {
            $this->throwUnAuthorizedException($deliveryExecution);
        }
    }

    /**
     * Check if delivery id proctored
     *
     * @param string $deliveryId
     * @param User $user
     * @return bool
     * @internal param core_kernel_classes_Resource $delivery
     */
    public function isProctored($deliveryId, User $user)
    {
        $delivery = $this->getResource($deliveryId);
        $proctored = $delivery->getOnePropertyValue($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR));
        $deliverySyncService = $this->getServiceLocator()->get(DeliverySyncService::SERVICE_ID);

        return $proctored instanceof \core_kernel_classes_Resource
            ? $proctored->getUri() == ProctorService::ACCESSIBLE_PROCTOR_ENABLED
            : $deliverySyncService->isProctoredByDefault();
    }

    /**
     * Throw the appropriate Exception
     *
     * @param DeliveryExecution $deliveryExecution
     * @throws UnAuthorizedException
     */
    protected function throwUnAuthorizedException(DeliveryExecution $deliveryExecution)
    {
        $errorPage = _url('awaitingAuthorization', 'DeliveryServer', 'taoProctoring', array('deliveryExecution' => $deliveryExecution->getIdentifier()));
        throw new UnAuthorizedException($errorPage, 'Proctor authorization missing');
    }

    public function isSuitable(User $user, $deliveryId = null)
    {
        return true;
    }
}

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
namespace oat\taoProctoring\test\integration\model\authorization;

use core_kernel_classes_Resource;
use oat\oatbox\user\User;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\taoProctoring\model\ProctorService;
use Prophecy\Argument;
use Prophecy\Prophet;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;

/**
 * Test the ProctorAuthorizationProvider
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProviderTest extends TaoPhpUnitTestRunner
{
    /**
     * Get the mocked delivery with enabled proctoring
     * @return object
     */
    protected function getDelivery()
    {
        $prophecy = $this->prophesize(core_kernel_classes_Resource::class);
        $prophecy->getUri()->willReturn('fakeDelivery');
        $prophecy->getOnePropertyValue(Argument::any())->willReturn(new core_kernel_classes_Resource(ProctorService::ACCESSIBLE_PROCTOR_ENABLED));

        return $prophecy->reveal();
    }

    /**
     * Get the mocked delivery execution
     *
     * @param string $executionStateUri the state of the delivery execution
     * @return DeliveryExecution the mocked delivery execution
     */
    protected function getDeliveryExecution($executionStateUri = ProctoredDeliveryExecution::STATE_AUTHORIZED)
    {
        $pState = $this->prophesize(\core_kernel_classes_Resource::class);
        $pState->getUri()->willReturn($executionStateUri);

        $prophecy = $this->prophesize(DeliveryExecutionInterface::class);
        $prophecy->getDelivery()->willReturn($this->getDelivery());
        $prophecy->getState()->willReturn($pState->reveal());
        $prophecy->getIdentifier()->willReturn('fakeDeliveryExecution');
        $prophecy->setState(Argument::any())->will(function($args) use ($prophecy){
            $prophecy->getState()->willReturn(new \core_kernel_classes_Resource($args[0]));
        });

        return $prophecy->reveal();
    }

    /**
     * Test the ProctorAuthorizationProvider API
     */
    public function testGetAuthorizationProviderAPI()
    {
        $authorizationProvider = new ProctorAuthorizationProvider();
        $this->assertInstanceOf(AuthorizationProvider::class, $authorizationProvider, "Check if the provider implements the authorizationProvider interface");
    }

    /**
     * Test the ProctorAuthorizationProvider#isAuthorized method
     */
    public function testIsAuthorized()
    {
        $authorizationProvider = new ProctorAuthorizationProvider();
        $authorizationProvider->setServiceLocator($this->getServiceManagerProphecy());
        $userP = $this->prophesize(User::class);
        $userP->getRoles()->willReturn([]);

        $authorized = $this->getDeliveryExecution(ProctoredDeliveryExecution::STATE_AUTHORIZED);
        $authorizationProvider->verifyResumeAuthorization($authorized, $userP->reveal());
    }

    /**
     * @expectedException \oat\taoDelivery\model\authorization\UnAuthorizedException
     */
    public function testIsUnauthorized()
    {
        $ttas = $this->prophesize(TestTakerAuthorizationService::class);
        $sl = $this->prophesize(ServiceLocatorInterface::class);
        $sl->get(TestTakerAuthorizationService::SERVICE_ID)->willReturn(new TestTakerAuthorizationService());
        $authorizationProvider = new ProctorAuthorizationProvider();
        $authorizationProvider->setServiceLocator($sl->reveal());
        $user = $this->prophesize(User::class)->reveal();
        $unauthorized = $this->getDeliveryExecution(ProctoredDeliveryExecution::STATE_FINISHIED);
        $authorizationProvider->verifyResumeAuthorization($unauthorized, $user);
    }
}

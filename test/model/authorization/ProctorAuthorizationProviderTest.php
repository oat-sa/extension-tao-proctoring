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
namespace oat\taoProctoring\test\model\authorization;

use Prophecy\Argument;
use Prophecy\Prophet;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDelivery\model\authorization\UnAuthorizedException;
use oat\oatbox\user\User;
use oat\taoProctoring\model\EligibilityService;

/**
 * Test the ProctorAuthorizationProvider
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProviderTest extends TaoPhpUnitTestRunner
{
    /**
     * Get the mocked delivery execution
     *
     * @param string $executionStateUri the state of the delivery execution
     * @return DeliveryExecution the mocked delivery execution
     */
    protected function getDeliveryExecution($executionStateUri = ProctoredDeliveryExecution::STATE_AUTHORIZED)
    {
        $prophet = new Prophet();
        $prophecy = $prophet->prophesize();
        $prophecy->willImplement(DeliveryExecution::class);
        $prophecy->getDelivery()->willReturn(new \core_kernel_classes_Resource('fakeDelivery'));
        $prophecy->getState()->willReturn(new \core_kernel_classes_Resource($executionStateUri));
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
        $user = $this->prophesize(User::class)->reveal();
        
        $authorized = $this->getDeliveryExecution(ProctoredDeliveryExecution::STATE_AUTHORIZED);
        $authorizationProvider->verifyResumeAuthorization($authorized, $user);
    }
    
    /**
     * @expectedException oat\taoDelivery\model\authorization\UnAuthorizedException
     */
    public function testIsUnauthorized()
    {
        $authorizationProvider = new ProctorAuthorizationProvider();
        $authorizationProvider->setServiceLocator($this->getServiceManagerProphecy());
        $user = $this->prophesize(User::class)->reveal();
        $unauthorized = $this->getDeliveryExecution(ProctoredDeliveryExecution::STATE_PAUSED);
        $authorizationProvider->verifyResumeAuthorization($unauthorized,$user);
    }
}

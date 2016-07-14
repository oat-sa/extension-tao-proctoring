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

/**
 * Test the ProctorAuthorizationProvider
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationProviderTest extends TaoPhpUnitTestRunner
{

    /**
     * Get the mocked provider
     *
     * @param string $executionStateUri the state of the delivery execution
     * @return ProctorAuthorizationProvider the mocked provider
     */
    protected function getProvider($executionStateUri = ProctoredDeliveryExecution::STATE_AUTHORIZED)
    {
        $prophet = new Prophet();
        $prophecy = $prophet->prophesize();
        $prophecy->willImplement(DeliveryExecution::class);
        $prophecy->getState()->willReturn(new \core_kernel_classes_Resource($executionStateUri));
        $prophecy->setState(Argument::any())->will(function($args) use ($prophecy){
            $prophecy->getState()->willReturn(new \core_kernel_classes_Resource($args[0]));
        });

        return  new ProctorAuthorizationProvider($prophecy->reveal());
    }

    /**
     * Test the ProctorAuthorizationProvider API
     */
    public function testGetAuthorizationProviderAPI()
    {
        $authorizationProvider = $this->getProvider();
        $this->assertInstanceOf(AuthorizationProvider::class, $authorizationProvider, "Check if the provider implements the authorizationProvider interface");
    }

    /**
     * Test the ProctorAuthorizationProvider#isAuthorized method
     */
    public function testIsAuthorized()
    {
        $authorizationProvider = $this->getProvider(ProctoredDeliveryExecution::STATE_AUTHORIZED);
        $this->assertTrue($authorizationProvider->isAuthorized());

        $authorizationProvider = $this->getProvider(ProctoredDeliveryExecution::STATE_PAUSED);
        $this->assertFalse($authorizationProvider->isAuthorized());
    }

    /**
     * Test the ProctorAuthorizationProvider#grant method
     */
    public function testGrant()
    {
        $authorizationProvider = $this->getProvider(ProctoredDeliveryExecution::STATE_PAUSED);
        $this->assertFalse($authorizationProvider->isAuthorized());
        $this->assertTrue($authorizationProvider->grant());
        $this->assertTrue($authorizationProvider->isAuthorized());
    }

    /**
     * Test the ProctorAuthorizationProvider#revoke method
     */
    public function testRevoke()
    {
        $authorizationProvider = $this->getProvider();
        $this->assertTrue($authorizationProvider->isAuthorized());
        $this->assertTrue($authorizationProvider->revoke());
        $this->assertFalse($authorizationProvider->isAuthorized());
    }
}

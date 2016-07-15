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
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\authorization\AuthorizationProvider;
use oat\taoDelivery\model\authorization\AuthorizationService;
use oat\taoDelivery\model\authorization\DeliveryAuthorizationProvider;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\authorization\ProctorAuthorizationProvider;
use oat\taoProctoring\model\authorization\ProctorDeliveryAuthorizationService;
use oat\tao\test\TaoPhpUnitTestRunner;

/**
 * Test the ProctorDeliveryAuthorizationService
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class ProctorAuthorizationServiceTest extends TaoPhpUnitTestRunner
{

    /**
     * Get the Mocked ProctorDeliveryAuthorizationService
     * @param boolean $canByPassProctor used to mock the eligibility service method
     * @return ProctorDeliveryAuthorizationService
     */
    protected function getProctorDeliveryAuthorizationService($canByPassProctor = false)
    {
        $noopResource = new \core_kernel_classes_Resource('foo');

        $prophet = new Prophet();
        $prophecy = $prophet->prophesize();
        $prophecy->willExtend(EligibilityService::class);

        $prophecy->getTestCenter(Argument::any(), Argument::any())->willReturn($noopResource);
        $prophecy->getEligibility(Argument::any(), Argument::any())->willReturn($noopResource);
        $prophecy->canByPassProctor(Argument::any())->willReturn($canByPassProctor);


        $eligibilityService = $prophecy->reveal();

        $proctorAuthorizationService = new ProctorDeliveryAuthorizationService();
        $proctorAuthorizationService->setEligibilityService($eligibilityService);

        return $proctorAuthorizationService;
    }

    /**
     * Test the ProctorAuthorizationService API
     */
    public function testGetAuthorizationServiceAPI()
    {
        $authorizationService = $this->getProctorDeliveryAuthorizationService();
        $this->assertInstanceOf(AuthorizationService::class, $authorizationService);
        $this->assertInstanceOf(ConfigurableService::class, $authorizationService);
    }

    /**
     * Create a dummy variable for a DeliveryExecution
     * @return DeliveryExecution the dummy variable
     */
    protected function getDeliveryExecution()
    {
        $prophet = new Prophet();
        $prophecy = $prophet->prophesize();
        $prophecy->willImplement(DeliveryExecution::class);
        $prophecy->getDelivery()->willReturn(new \core_kernel_classes_Resource('foo'));

        return $prophecy->reveal();
    }

    /**
     * Create a dummy variable for a User
     * @return User the dummy variable
     */
    protected function getUser()
    {
        $prophet = new Prophet();
        $prophecy = $prophet->prophesize();
        $prophecy->willImplement(User::class);

        return $prophecy->reveal();
    }

    /**
     * Test getting the authoriation provider when the execution can by-pass proctoring
     */
    public function testGetAuthorizationProviderByPassProctoring()
    {
        $authorizationService = $this->getProctorDeliveryAuthorizationService(true);
        $provider = $authorizationService->getAuthorizationProvider($this->getDeliveryExecution(), $this->getUser());

        $this->assertInstanceOf(AuthorizationProvider::class, $provider);
        $this->assertInstanceOf(DeliveryAuthorizationProvider::class, $provider);
    }

    /**
     * Test getting the authoriation provider when the execution is proctored
     */
    public function testGetAuthorizationProviderProctoring()
    {
        $authorizationService = $this->getProctorDeliveryAuthorizationService(false);
        $provider = $authorizationService->getAuthorizationProvider($this->getDeliveryExecution(), $this->getUser());

        $this->assertInstanceOf(AuthorizationProvider::class, $provider);
        $this->assertInstanceOf(ProctorAuthorizationProvider::class, $provider);

    }
}

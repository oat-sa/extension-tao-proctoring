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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\taoProctoring\test;

use oat\taoProctoring\model\implementation\DeliveryAuthorizationService;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoProctoring\model\execution\DeliveryExecution;

class DeliveryAuthorizationServiceTest extends TaoPhpUnitTestRunner
{

    /** @var  DeliveryAuthorizationService */
    private $authorizationService;

    public static function setUpBeforeClass()
    {

    }

    public function setUp()
    {
        parent::setUp();
        TaoPhpUnitTestRunner::initTest();
        $this->authorizationService = new DeliveryAuthorizationService();
    }

    public function testGrantAuthorization()
    {
        $deliveryExecution = $this->getDEMock();
        $this->assertFalse($this->authorizationService->isAuthorized($deliveryExecution));
        $this->assertTrue($this->authorizationService->grantAuthorization($deliveryExecution));
        $this->assertTrue($this->authorizationService->isAuthorized($deliveryExecution));
    }

    public function testRevokeAuthorization()
    {
        $deliveryExecution = $this->getDEMock();
        $this->authorizationService->grantAuthorization($deliveryExecution);
        $this->assertTrue($this->authorizationService->isAuthorized($deliveryExecution));
        $this->assertTrue($this->authorizationService->revokeAuthorization($deliveryExecution));
        $this->assertFalse($this->authorizationService->isAuthorized($deliveryExecution));
    }

    public function testIsAuthorized()
    {
        $deliveryExecution = $this->getDEMock();
        $this->authorizationService->grantAuthorization($deliveryExecution);
        $this->assertTrue($this->authorizationService->isAuthorized($deliveryExecution));
        $this->authorizationService->revokeAuthorization($deliveryExecution);
        $this->assertFalse($this->authorizationService->isAuthorized($deliveryExecution));

        //delivery in active state treats as authorized.
        $deliveryExecution->setState(DeliveryExecution::STATE_ACTIVE);
        $this->assertTrue($this->authorizationService->isAuthorized($deliveryExecution));
    }

    private function getDEMock()
    {
        return new DeliveryExecutionMock(new \taoDelivery_models_classes_execution_OntologyDeliveryExecution('test_uri'));
    }
}

class DeliveryExecutionMock extends \oat\taoProctoring\model\execution\DeliveryExecution
{
    private $state;

    public function __construct(\taoDelivery_models_classes_execution_DeliveryExecution $implementation)
    {
        parent::__construct($implementation);
        $this->state = new \core_kernel_classes_Resource('fake_state');
    }

    public function setState($state)
    {
        $this->state = new \core_kernel_classes_Resource($state);
    }

    public function getState()
    {
        return $this->state;
    }
}
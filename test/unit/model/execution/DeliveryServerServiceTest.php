<?php

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\execution;

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\DeliveryServerService;
use oat\generis\test\TestCase;

class DeliveryServerServiceTest extends TestCase
{
    /**
     * @var DeliveryExecution|\PHPUnit\Framework\MockObject\MockObject
     */
    private $deliveryExecutionMock;

    /**
     * @var DeliveryExecutionStateService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $deliveryExecutionStateServiceMock;
    /**
     * @var \core_kernel_classes_Resource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stateResource;

    protected function setUp(): void
    {
        $this->deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $this->stateResource = $this->createMock(\core_kernel_classes_Resource::class);
        $this->deliveryExecutionMock->expects($this->once())->method('getState')->willReturn($this->stateResource);
        $this->deliveryExecutionStateServiceMock = $this->createMock(DeliveryExecutionStateService::class);
    }

    public function testRevokeActiveSession()
    {
        $this->stateResource->expects($this->once())->method('getUri')->willReturn(DeliveryExecution::STATE_ACTIVE);

        $this->deliveryExecutionStateServiceMock->expects($this->once())->method('pauseExecution')->with($this->deliveryExecutionMock, [
            'reasons' => ['category' => 'focus-loss'],
            'comment' => 'Assessment has been paused due to attempt to switch to another window/tab.',
        ]);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            DeliveryExecutionStateService::SERVICE_ID => $this->deliveryExecutionStateServiceMock
        ]);

        $service = new DeliveryServerService();
        $service->setServiceLocator($serviceLocatorMock);
        $service->revoke($this->deliveryExecutionMock);

    }

    public function testRevokePauseSession()
    {
        $this->stateResource->expects($this->once())->method('getUri')->willReturn(DeliveryExecution::STATE_PAUSED);

        $this->deliveryExecutionStateServiceMock->expects($this->never())->method('pauseExecution');
        $serviceLocatorMock = $this->getServiceLocatorMock([
            DeliveryExecutionStateService::SERVICE_ID => $this->deliveryExecutionStateServiceMock
        ]);

        $service = new DeliveryServerService();
        $service->setServiceLocator($serviceLocatorMock);
        $service->revoke($this->deliveryExecutionMock);

    }
}

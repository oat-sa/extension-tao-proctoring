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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */
declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\deliveryLog\listener;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use oat\generis\test\TestCase;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\listener\DeliveryLogTimerAdjustedEventListener;
use oat\taoProctoring\model\event\DeliveryExecutionTimerAdjusted;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoProctoring\model\ProctoringContextService;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use qtism\data\AssessmentItemRef;
use qtism\runtime\tests\AssessmentTestSession;

class DeliveryLogTimerAdjustedEventListenerTest extends TestCase
{
    /**
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     */
    public function testAdjustTime(): void
    {
        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $deliveryExecutionMock->method('getIdentifier')->willReturn('DEPHPUNITID');

        $eventMock = $this->createMock(DeliveryExecutionTimerAdjusted::class);
        $eventMock->method('getReason')->willReturn(['reason']);
        $eventMock->method('getSeconds')->willReturn(1);
        $eventMock->method('getDeliveryExecution')->willReturn($deliveryExecutionMock);

        $deliveryLogServiceMock = $this->createMock(DeliveryLog::class);
        $deliveryLogServiceMock->method('log')->with(
            // de id
            'DEPHPUNITID',
            'TEST_ADJUSTED_TIME',
            [
                'reason' => ['reason'],
                'increment' => 1,
                'context' => 'context',
                'itemId' => 'PHPUnitItemID',
            ]
        );

        $itemRefMock = $this->createMock(AssessmentItemRef::class);
        $itemRefMock->method('getIdentifier')->willReturn('PHPUnitItemID');

        $assessmentTestSessionMock = $this->createMock(AssessmentTestSession::class);
        $assessmentTestSessionMock->method('getCurrentAssessmentItemRef')->willReturn($itemRefMock);

        $testSessionServiceMock = $this->createMock(TestSessionService::class);
        $testSessionServiceMock->method('getTestSession')->willReturn($assessmentTestSessionMock);

        $proctoringContextServiceMock = $this->createMock(ProctoringContextService::class);
        $proctoringContextServiceMock->method('getContextString')->willReturn('context');

        $serviceLocatorMock = $this->getServiceLocatorMock([
            DeliveryLog::SERVICE_ID => $deliveryLogServiceMock,
            TestSessionService::SERVICE_ID => $testSessionServiceMock,
            ProctoringContextService::class => $proctoringContextServiceMock,
        ]);

        $listener = new DeliveryLogTimerAdjustedEventListener();
        $listener->setServiceLocator($serviceLocatorMock);
        $listener->logTimeAdjustment($eventMock);
        
        $this->assertTrue(true);
    }
}

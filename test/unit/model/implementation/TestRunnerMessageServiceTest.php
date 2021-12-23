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
 * Copyright (c) 2021  (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\implementation;

use oat\oatbox\service\ServiceManager;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\taoProctoring\model\implementation\TestRunnerMessageService;
use qtism\runtime\tests\AssessmentTestSession;
use PHPUnit\Framework\TestCase;

final class TestRunnerMessageServiceTest extends TestCase
{
    /**
     * @var TestRunnerMessageService
     */
    private $testRunnerMessageService;

    /**
     * @var ServiceManager
     */
    private $serviceManager;

    /**
     * @var FeatureFlagCheckerInterface
     */
    private $featureFlagChecker;

    /**
     * @var AssessmentTestSession
     */
    private $assessmentTestSession;

    protected function setUp(): void
    {
        $this->testRunnerMessageService = new TestRunnerMessageService();
        $this->featureFlagChecker = $this->createMock(FeatureFlagCheckerInterface::class);
        $this->serviceManager = $this->createMock(ServiceManager::class);
        $this->serviceManager->method('get')->willReturn($this->featureFlagChecker);
        $this->assessmentTestSession = $this->createMock(AssessmentTestSession::class);
    }

    /**
     * @dataProvider providerProctorRoles
     */
    public function testGetPausedStateMessage(array $proctorRoles, string $message): void
    {
        $testRunnerMessageService = $this->getClassTestRunnerMessageService($this->serviceManager, ['proctorRoles' => $proctorRoles]);
        $pausedStateMessage = $testRunnerMessageService->getPausedStateMessageFrom($this->assessmentTestSession);

        self::assertSame($message, $pausedStateMessage);
    }

    public function testGetPausedStateMessageReturnEmpty(): void
    {
        $this->featureFlagChecker->method('isEnabled')->willReturn(true);
        $testRunnerMessageService = $this->getClassTestRunnerMessageService($this->serviceManager);
        $pausedStateMessage = $testRunnerMessageService->getPausedStateMessageFrom($this->assessmentTestSession);

        self::assertEmpty($pausedStateMessage);
    }

    public function providerProctorRoles(): array
    {
        return [
            'message with defined roles' => [
                (new \common_session_AnonymousSession())->getUserRoles(),
                'The assessment has been suspended. To resume your assessment, please relaunch it and contact your proctor if required.'
            ],
            'message without defined roles' => [
                [],
                'The assessment has been suspended. To resume your assessment, please relaunch it.'
            ]
        ];
    }

    /**
     * @param array $options
     * @param ServiceManager $serviceManager
     * @return TestRunnerMessageService
     */
    private function getClassTestRunnerMessageService($serviceManager, $options = []): TestRunnerMessageService
    {
        return new class ($options, $serviceManager) extends TestRunnerMessageService {
            /**
             * @var ServiceManager
             */
            private $serviceManager;

            public function __construct($options = [], $serviceManager)
            {
                parent::__construct($options);
                $this->serviceManager = $serviceManager;
            }

            /**
             * @param AssessmentTestSession $testSession
             * @return string
             */
            public function getPausedStateMessageFrom(AssessmentTestSession $testSession): string
            {
                return parent::getPausedStateMessage($testSession);
            }

            /**
             * @return ServiceManager
             */
            public function getServiceLocator(): ServiceManager
            {
                return $this->serviceManager;
            }
        };
    }
}

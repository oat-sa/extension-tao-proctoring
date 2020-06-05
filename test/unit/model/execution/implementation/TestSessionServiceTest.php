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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\execution\implementation;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoProctoring\model\implementation\TestSessionService;
use oat\taoQtiTest\models\runner\session\TestSession;
use oat\taoQtiTest\models\runner\time\QtiTimeConstraint;
use qtism\common\datatypes\Duration;
use qtism\runtime\tests\TimeConstraintCollection;

class TestSessionServiceTest extends TestCase
{
    /**
     * @var TestSessionService
     */
    private $subject;

    /**
     * @var TestSession|MockObject
     */
    private $testSessionMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testSessionMock = $this->createMock(TestSession::class);

        $this->subject = new TestSessionService();
    }

    public function testGetSmallestMaxTimeConstraint_NoConstraints_ReturnsNull(): void
    {
        $this->testSessionMock->method('getTimeConstraints')
            ->willReturn(new TimeConstraintCollection());

        self::assertNull(
            $this->subject->getSmallestMaxTimeConstraint($this->testSessionMock),
            'Method must return correct response in case session does not have time constraints.'
        );
    }

    public function testGetSmallestMaxTimeConstraint_NoConstraintsWithMaximumRemainingTime_ReturnsNull(): void
    {
        $timeConstraintSession = $this->mockQtiTimeConstraint(false);
        $timeConstraintTestPart = $this->mockQtiTimeConstraint(false);
        $timeConstraintsCollection = new TimeConstraintCollection([$timeConstraintSession, $timeConstraintTestPart]);

        $this->testSessionMock->method('getTimeConstraints')
            ->willReturn($timeConstraintsCollection);

        self::assertNull(
            $this->subject->getSmallestMaxTimeConstraint($this->testSessionMock),
            'Method must return correct response in case constraints do not have max time limits.'
        );
    }

    public function testGetSmallestMaxTimeConstraint_OneConstraintWithMaximumRemainingTime(): void
    {
        $timeConstraintSession = $this->mockQtiTimeConstraint(false);
        $timeConstraintTestPart = $this->mockQtiTimeConstraint(false);
        $timeConstraintTest = $this->mockQtiTimeConstraint($this->mockQtiDuration(600));
        $timeConstraintsCollection = new TimeConstraintCollection([
            $timeConstraintSession,
            $timeConstraintTestPart,
            $timeConstraintTest
        ]);

        $expectedConstraint = $timeConstraintTest;
        $this->testSessionMock->method('getTimeConstraints')
            ->willReturn($timeConstraintsCollection);

        self::assertSame(
            $expectedConstraint,
            $this->subject->getSmallestMaxTimeConstraint($this->testSessionMock),
            'Method must return correct response in case there is one constraint with max time limit.'
        );
    }

    public function testGetSmallestMaxTimeConstraint_MultipleConstraintsWithMaximumRemainingTime(): void
    {
        $timeConstraintTest = $this->mockQtiTimeConstraint($this->mockQtiDuration(600));
        $timeConstraintTestPart = $this->mockQtiTimeConstraint($this->mockQtiDuration(450));
        $timeConstraintSession = $this->mockQtiTimeConstraint($this->mockQtiDuration(300));
        $timeConstraintsCollection = new TimeConstraintCollection([
            $timeConstraintSession,
            $timeConstraintTestPart,
            $timeConstraintTest
        ]);

        $expectedConstraint = $timeConstraintSession;
        $this->testSessionMock->method('getTimeConstraints')
            ->willReturn($timeConstraintsCollection);

        self::assertSame(
            $expectedConstraint,
            $this->subject->getSmallestMaxTimeConstraint($this->testSessionMock),
            'Method must return correct response in case there multiple constraints with max time limit.'
        );
    }

    /**
     * @param int $maxRemainingTime
     * @return Duration
     */
    private function mockQtiDuration(int $maxRemainingTime): Duration
    {
        $durationMock = $this->createMock(Duration::class);
        $durationMock->method('getSeconds')
            ->willReturn($maxRemainingTime);

        return $durationMock;
    }

    /**
     * @param Duration|bool $maxRemainingTime
     * @return QtiTimeConstraint|MockObject
     */
    private function mockQtiTimeConstraint($duration): QtiTimeConstraint
    {
        $qtiTimeConstraintMock = $this->createMock(QtiTimeConstraint::class);
        $qtiTimeConstraintMock->method('getMaximumRemainingTime')
            ->willReturn($duration);

        return $qtiTimeConstraintMock;
    }
}


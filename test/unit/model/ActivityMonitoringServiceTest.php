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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\test\unit\model;

use oat\generis\test\TestCase;
use oat\taoProctoring\model\ActivityMonitoringService;

/**
 * Class ActivityMonitoringServiceTest
 * @package oat\taoProctoring\test\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ActivityMonitoringServiceTest extends TestCase
{
    public function testGetTimeKeys()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $service = new ActivityMonitoringService();

        $timeKeys = $service->getTimeKeys(new \DateInterval('PT1M'), clone($date));
        $this->assertEquals(60, count($timeKeys));
        $this->assertEquals(((int)$date->format('i') + 1) % 60, (int)$timeKeys[0]->format('i'));
        $this->assertEquals(0, $timeKeys[0]->format('s'));

        $timeKeys = $service->getTimeKeys(new \DateInterval('PT1H'), clone($date));
        $this->assertEquals(24, count($timeKeys));
        $this->assertEquals(((int)$date->format('H') + 1) % 24, (int)$timeKeys[0]->format('H'));
        $this->assertEquals(0, $timeKeys[0]->format('i'));

        $timeKeys = $service->getTimeKeys(new \DateInterval('P1D'), clone($date));
        $tomorrow = (new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('P1D'));
        $this->assertEquals($tomorrow->format('t'), count($timeKeys));
        $this->assertEquals($tomorrow->format('d'), $timeKeys[0]->format('d'));
        $this->assertEquals('00', $timeKeys[0]->format('H'));

        $timeKeys = $service->getTimeKeys(new \DateInterval('P1M'), clone($date));
        $this->assertEquals(12, count($timeKeys));
        $this->assertEquals((int)$date->format('m') % 12 + 1, (int)$timeKeys[0]->format('m'));
        $this->assertEquals(0, $timeKeys[0]->format('H'));
        $this->assertEquals('01', $timeKeys[0]->format('d'));
    }
}

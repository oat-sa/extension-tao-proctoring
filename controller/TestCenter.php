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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\controller;

use oat\taoProctoring\helpers\Breadcrumbs;
use oat\taoProctoring\helpers\TestCenter as TestCenterHelper;

/**
 * Proctoring Test Center controllers
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class TestCenter extends Proctoring
{

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $data = array(
            'list' => $testCenters
        );

        if ($this->isXmlHttpRequest()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-index',
                $data,
                array(
                    Breadcrumbs::testCenters()
                )
            );
        }
    }

    /**
     * Displays the three action box for the test center
     */
    public function testCenter()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $testCenter  = $this->getCurrentTestCenter();
        $data = array(
            'testCenter' => $testCenter->getUri(),
            'title' => __('Test site %s', $testCenter->getLabel()),
            'list' => TestCenterHelper::getTestCenterActions($testCenter)
        );

        if ($this->isXmlHttpRequest()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-testcenter',
                $data,
                array(
                    Breadcrumbs::testCenters(),
                    Breadcrumbs::testCenter($testCenter, $testCenters)
                )
            );
        }
    }
}
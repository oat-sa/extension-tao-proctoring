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

use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;
use oat\taoProctoring\model\textConverter\ProctoringTextConverterTrait;

/**
 * Proctoring Test Center controllers for test center screens
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class TestCenter extends ProctoringModule
{
    use ProctoringTextConverterTrait;

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function index()
    {
        $testCenters = TestCenterHelper::getTestCenters();
        $data = array(
            'list' => $testCenters,
            'administrator' => $this->isAdmin() //check if the current user is a test site administrator or not
        );

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-index',
                $data,
                array(
                    BreadcrumbsHelper::testCenters()
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
            'title' => sprintf($this->convert('Test site %s'), $testCenter->getLabel()),
            'list' => TestCenterHelper::getTestCenterActions($testCenter)
        );

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson($data);
        } else {
            $this->composeView(
                'testcenters-testcenter',
                $data,
                array(
                    BreadcrumbsHelper::testCenters(),
                    BreadcrumbsHelper::testCenter($testCenter, $testCenters)
                )
            );
        }
    }
}
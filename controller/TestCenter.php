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

use oat\taoProctoring\controller\Proctoring;
use oat\taoProctoring\helpers\Breadcrumbs;
use \core_kernel_classes_Resource;

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

        $testCenters = $this->getTestCenters();

        $this->composeView(
            'testcenters-index',
            array(
                'list' => $testCenters
            ), array(
                Breadcrumbs::testCenters()
            )
        );
    }

    /**
     * Displays the three action box for the test center
     */
    public function testCenter()
    {
        $testCenters = $this->getTestCenters();
        $testCenter  = $this->getCurrentTestCenter();

        $this->composeView(
            'testcenters-testcenter',
            array(
                'id' => $testCenter->getUri(), //change key to testCenter for better consistency
                'title' => __('Test site %s', $testCenter->getLabel()),
                'list' => $this->getTestCenterActions($testCenter)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, $testCenters)
            )
        );
    }

    /**
     * Gets a list of entries available for a test site
     *
     * @param $testCenter core_kernel_classes_Resource
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    protected function getTestCenterActions(core_kernel_classes_Resource $testCenter)
    {

        $actionDiagnostics = Breadcrumbs::diagnostics($testCenter);
        $actionDeliveries  = Breadcrumbs::deliveries($testCenter);
        $actionReporting   = Breadcrumbs::reporting($testCenter);

        $actions = array(
            array(
                'url' => $actionDiagnostics['url'],
                'label' => __('Readiness Check'),
                'content' => __('Check the compatibility of the current workstation and see the results'),
                'text' => __('Go')
            ),
            array(
                'url' => $actionDeliveries['url'],
                'label' => __('Deliveries'),
                'content' => __('Monitor and manage the deliveries of the test site'),
                'text' => __('Go')
            ),
            array(
                'url' => $actionReporting['url'],
                'label' => __('Assessment Activity Reporting'),
                'content' => __('Generate and review test histories'),
                'text' => __('Go')
            ),
        );

        return $actions;
    }
}
<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;

use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;

/**
 * Proctoring Reporting controllers for the assessment activity reporting screen.
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Reporting extends ProctoringModule
{
    /**
     * Display the activity reporting of the current test center
     */
    public function index()
    {

        $testCenter     = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->setData('title', __('Assessment Activity Reporting for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'reporting-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => TestCenterHelper::getReports($testCenter, $requestOptions),
                'categories' => $this->getAllReasonsCategories(),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::reporting(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::diagnostics($testCenter),
                        BreadcrumbsHelper::deliveries($testCenter),
                    )
                )
            )
        );
    }

    /**
     * Returns array of reports to datatable
     */
    public function reports(){
        $testCenter     = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();
        $this->returnJson(TestCenterHelper::getReports($testCenter, $requestOptions));
    }
}
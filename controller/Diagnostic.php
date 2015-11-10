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

use oat\taoProctoring\helpers\Breadcrumbs;
use oat\taoProctoring\helpers\TestCenter as TestCenterHelper;

/**
 * Proctoring controller related to Readyness Check tool
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Diagnostic extends Proctoring
{

    /**
     * Display the list of all readiness checks performed on the given test center
     * It also allows launching new ones.
     */
    public function index(){

        $testCenter = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->setData('title', __('Readiness Check for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'diagnostic-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => TestCenterHelper::getDiagnostics($testCenter, $requestOptions)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                Breadcrumbs::diagnostics(
                    $testCenter,
                    array(
                        Breadcrumbs::deliveries($testCenter),
                        Breadcrumbs::reporting($testCenter)
                    )
                )
            )
        );
    }

}
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
 * Proctoring Diagnostic controller for the readiness check screen
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Diagnostic extends ProctoringModule
{
    /**
     * Display the list of all readiness checks performed on the given test center
     * It also allows launching new ones.
     */
    public function index(){

        $testCenter = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->setData('title', __('Readiness Check for test site %s', _dh($testCenter->getLabel())));
        $this->composeView(
            'diagnostic-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => TestCenterHelper::getDiagnostics($testCenter, $requestOptions),
                'config' => TestCenterHelper::getDiagnosticConfig($testCenter),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::diagnostics(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::deliveries($testCenter),
                    )
                )
            )
        );
    }

    /**
     * Display the diagnostic runner
     */
    public function diagnostic()
    {
        $testCenter = $this->getCurrentTestCenter();

        $this->setData('title', __('Readiness Check for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'diagnostic-runner',
            array(
                'testCenter' => $testCenter->getUri(),
                'config' => TestCenterHelper::getDiagnosticConfig($testCenter),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::diagnostics(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::deliveries($testCenter),
                    )
                )
            )
        );
    }

    /**
     * Gets the list of diagnostic results
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function diagnosticData()
    {
        try {

            $testCenter = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions();
            $this->returnJson(TestCenterHelper::getDiagnostics($testCenter, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No diagnostic service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }

    /**
     * Removes diagnostic results
     *
     * @throws \common_Exception
     */
    public function remove()
    {
        $testCenter = $this->getCurrentTestCenter();

        $id = $this->getRequestParameter('id');

        $this->returnJson([
            'success' => TestCenterHelper::removeDiagnostic($testCenter, $id)
        ]);
    }
}
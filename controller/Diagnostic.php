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

use oat\taoProctoring\controller\Proctoring;
use oat\taoProctoring\helpers\Breadcrumbs;

/**
 * Proctoring Delivery controllers
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
        $diagnostics = $this->getDiagnostics($testCenter);

        $this->setData('title', __('Readiness Check for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'diagnostic-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => $this->paginate($diagnostics, $requestOptions)
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
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

    /**
     * Gets the list of readiness checks related to a test site
     *
     * @param $testCenter
     * @return array
     */
    private function getDiagnostics($testCenter) {
        $count = 10;
        $os = array('WinXP', 'Win7', 'Win8', 'Win10', 'Linux', 'Mac OS X');
        $browser = array('IE11', 'Edge', 'Firefox', 'Chrome', 'Safari', 'Opera');
        $performances = array('bad', 'medium', 'good');
        $bandwidth = array('30', '50', '70', '>100');
        $date = array('2015-09-16 13:04', '2015-09-21 10:23', '2015-10-06 09:34', '2015-10-18 11:43', '2015-10-29 14:53');
        $results = array();

        for ($i = 0; $i < $count; $i ++) {
            $id = $i + 1;
            $results[] = array(
                'id' => $id,
                'workstation' => 'Computer ' . $id,
                'os' => $os[array_rand($os)],
                'browser' => $browser[array_rand($browser)],
                'performance' => $performances[array_rand($performances)],
                'bandwidth' => $bandwidth[array_rand($bandwidth)],
                'date' => $date[array_rand($date)],
            );
        }

        return $results;
    }
   
}
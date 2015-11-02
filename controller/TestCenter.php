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
    public function testCenters()
    {

        $testCenters = $this->getTestCenters();

        $this->setData('testCenters', $testCenters);
        $this->composeView('Proctoring/testCenters.tpl', array(
            Breadcrumbs::testCenters()
        ));
    }

    /**
     * Displays the three action box for the test center
     */
    public function testCenter()
    {
        $testCenter =  $this->getCurrentTestCenter();

        $this->setData('label', $testCenter->getLabel());
        $this->composeView('Proctoring/testCenter.tpl',
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter)
        ));
    }

    /**
     * Gets a list of available Test Centers for the current proctor
     *
     * @return array
     */
    private function getTestCenters()
    {

        $entries = array();

        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i1000000001')),
            'label' => 'Room A',
            'text' => __('Go to')
        );
        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i1000000002')),
            'label' => 'Room B',
            'text' => __('Go to')
        );
        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i1000000003')),
            'label' => 'Room C',
            'text' => __('Go to')
        );

        return $entries;
    }
}
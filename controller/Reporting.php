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
class Reporting extends Proctoring
{
    public function index(){

        $testCenter = $this->getCurrentTestCenter();

        $this->composeView(
            'report',
            array(
                'id' => $testCenter->getUri(),
                'title' => __('Assessment Activity Reporting for test site %s', $testCenter->getLabel()),
                'list' => array(
                    array(
                        'url' => _url('index', 'Reporting', null, array('testCenter' => $testCenter->getUri())),
                        'label' => __('This page is under construction. Please go back later...'),
                        'text' => __('Refresh'),
                    ),
                )
            ),
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
            Breadcrumbs::reporting($testCenter,
                array(
                Breadcrumbs::diagnostics($testCenter),
                Breadcrumbs::deliveries($testCenter),
            ))
        ));
    }
   
}
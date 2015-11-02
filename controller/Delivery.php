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
class Delivery extends Proctoring
{

    /**
     * Displays the index page of the extension: list all available deliveries.
     */
    public function deliveries()
    {

        $testCenter = $this->getCurrentTestCenter();
        $deliveries = $this->getDeliveries($testCenter);

        $this->setData('deliveries', $deliveries);
        $this->composeView('Proctoring/deliveries.tpl',
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter),
            Breadcrumbs::deliveries($testCenter, $testCenter)
        ));
    }

    public function deliveryMonitoring()
    {

        $testCenter    = $this->getCurrentTestCenter();
        $delivery      = $this->getCurrentDelivery();
        $executionData = $this->getDeliveryExecutions($delivery);

        $this->setData('executionsData', $executionData);
        $this->composeView('Proctoring/testCenter.tpl',
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter),
            Breadcrumbs::deliveries($testCenter),
            Breadcrumbs::deliveryMonitoring($delivery)
        ));
    }

    /**
     * Gets the list of available deliveries for the selected test center
     *
     * @return array
     */
    private function getDeliveries(core_kernel_classes_Resource $testCenter)
    {

        $entries = array();

        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i2000000001')),
            'label' => 'Test A',
            'text' => __('Go to')
        );
        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i2000000002')),
            'label' => 'Test B',
            'text' => __('Manage')
        );
        $entries[] = array(
            'url' => _url('index', 'TestCenter', null, array('uri' => 'locam_ns#i2000000003')),
            'label' => 'Test C',
            'text' => __('Manage')
        );

        return $entries;
    }

    /**
     * Get the agregated data for a filtered set of delivery executions of a given delivery
     * This is performance critical, would need to find a way to optimize to obtain such information
     *
     * @return array
     */
    private function getDeliveryExecutions(core_kernel_classes_Resource $delivery)
    {

        $entries = array();

        $entries[] = array(
            'uri' => 'locam_ns#i4000000001',
            'testTaker' => array(
                'firstName' => 'Giacomo',
                'lastName' => 'Guilizzoni',
                'companyName' => 'Company ABC',
            ),
            'delivery' => array(
                'label' => 'Test A',
            ),
            'state' => array(
                //client will infer possible action based on the current status
                'status' => 'inProgress',
                'section' => array(
                    'label' => 'section B',
                    'position' => 2,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 1,
                    'total' => 9,
                    'time' => array(
                        //time unit in second, does not require microsecond precision for human monitoring
                        'elapsed' => 60,
                        'total' => 600
                    )
                )
            )
        );

        $entries[] = array(
            'uri' => 'locam_ns#i4000000002',
            'testTaker' => array(
                'firstName' => 'Marco',
                'lastName' => 'Botton',
                'companyName' => 'Company ABC',
            ),
            'delivery' => array(
                'label' => 'Test A',
            ),
            'state' => array(
                'status' => 'inProgress',
                'section' => array(
                    'label' => 'section A',
                    'position' => 1,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 5,
                    'total' => 8,
                    'time' => array(
                        'elapsed' => 540,
                        'total' => 600
                    )
                )
            )
        );

        return $entries;
    }
}
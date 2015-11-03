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
use \core_kernel_classes_Resource;

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

        $this->composeView(
            'deliveries-listing',
            array(
                'list' => $deliveries
            ),
            array(
            Breadcrumbs::testCenters(),
            Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
            Breadcrumbs::deliveries($testCenter,
                array(
                Breadcrumbs::diagnostics($testCenter),
                Breadcrumbs::reporting($testCenter)
            ))
        ));
    }

    public function monitoring()
    {

        $testCenter    = $this->getCurrentTestCenter();
        $delivery      = $this->getCurrentDelivery();
        $executionData = $this->getDeliveryExecutions($delivery);

        $this->composeView(
            'delivery-manager',
            array(
                'id' => $delivery->getUri(), //change key to delivery for better consistency
                'testSite' => $testCenter->getUri(), //change key to delivery for better consistency
                'set' => $executionData //change this to list for better consistency
            ),
            array(
                Breadcrumbs::testCenters(),
                Breadcrumbs::testCenter($testCenter, $this->getTestCenters()),
                Breadcrumbs::deliveries($testCenter,
                    array(
                        Breadcrumbs::diagnostics($testCenter),
                        Breadcrumbs::reporting($testCenter)
                    )
                ),
                Breadcrumbs::deliveryMonitoring($testCenter, $delivery, $this->getDeliveries())
            )
        );
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
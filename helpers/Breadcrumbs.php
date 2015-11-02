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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\helpers;

use \core_kernel_classes_Resource;

class Breadcrumbs{

    public static function testCenters(){
        return array(
            'id' => 'testCenters',
            'url' => _url('testCenters', 'Proctoring'),
            'label' => __('Home'),
        );
    }

    public static function testCenter(core_kernel_classes_Resource $testCenter){
        //list also other available test centers
        return array(
            'id' => 'testCenter',
            'url' => _url('testCenter', 'Proctoring', null, array('testCenter' => $testCenter->getUri())),
            'label' => $testCenter->getLabel(),
        );
    }

    public static function deliveries(core_kernel_classes_Resource $testCenter){
        return array(
            'id' => 'deliveries',
            'url' => _url('deliveries', 'Proctoring', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('deliveries')
        );
    }

    public static function deliveryMonitoring(core_kernel_classes_Resource $testCenter, core_kernel_classes_Resource $delivery){
        //list also other available deliveries
        return array(
            'id' => 'deliveryMonitoring',
            'url' => _url('delivery', 'Proctoring', null, array('testCenter' => $testCenter->getUri(), 'delivery' => $delivery->getUri())),
            'label' => $delivery->getLabel()
        );
    }
}
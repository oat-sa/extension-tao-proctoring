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
namespace oat\taoProctoring\model\implementation;

use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\mock\WebServiceMock;
use \core_kernel_classes_Resource;
use \core_kernel_classes_Class;

/**
 * Sample TestCenter Service for proctoring
 * 
 */
class TestCenterService extends ConfigurableService
{
    /**
     * Gets a list of available test centers
     *
     * @return array
     */
    public function getTestCenters() {
        $testCenters = WebServiceMock::loadJSON(dirname(__FILE__) . '/../mock/data/test-centers.json');
        foreach($testCenters as $k => $val) {
            $testCenter = new core_kernel_classes_Resource($val['id']);
            if (!$testCenter->exists()) {
                $objectClass = new \core_kernel_classes_Class(TAO_OBJECT_CLASS);
                $testCenter = $objectClass->createInstance($val['label'], 'temporarily generated test center', $val['id']);
            }
            $testCenters[$k] = $testCenter;
        }
        return $testCenters;
    }

    /**
     * Gets test center
     *
     * @param string $id
     * @return array
     */
    public function getTestCenter($id) {
        $testCenter = WebServiceMock::filter($this->getTestCenters(), function($center) use($id) {
            return !strnatcasecmp($id, $center->getUri());
        });

        if (count($testCenter)) {
            return current($testCenter);
        }
        return null;
    }

    /**
     * Gets a list of available test centers
     *
     * @param string $testCenterId
     * @param array [$options]
     * @return array
     */
    public function getDiagnostics($testCenterId) {
        return WebServiceMock::loadJSON(dirname(__FILE__) . '/../mock/data/diagnostics.json');
    }
    
}

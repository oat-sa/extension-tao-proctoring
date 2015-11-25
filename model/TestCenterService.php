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
namespace oat\taoProctoring\model;

use oat\oatbox\user\User;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use tao_models_classes_ClassService;

/**
 * TestCenter Service for proctoring
 * 
 */
class TestCenterService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#TestCenter';

    const PROPERTY_PROCTORS_URI = 'http://www.tao.lu/Ontologies/TAOTestCenter.rdf#proctor';

    /**
     * return the test center top level class
     *
     * @access public
     * @return core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return new core_kernel_classes_Class(self::CLASS_URI);
    }

    /**
     * Get test centers administered by a proctor
     *
     * @param User $user
     * @return core_kernel_classes_Resource[]
     * @throws \common_exception_Error
     */
    public function getTestCentersByProctor(User $user) {
        $testCenters = array();
        foreach ($user->getPropertyValues(self::PROPERTY_PROCTORS_URI) as $id) {
            $testCenters[] = new core_kernel_classes_Resource($id);
        }
        return $testCenters;
    }

    /**
     * Gets test center
     *
     * @param string $testCenterUri
     * @return core_kernel_classes_Resource
     */
    public function getTestCenter($testCenterUri) {
        return new core_kernel_classes_Resource($testCenterUri);
    }
}

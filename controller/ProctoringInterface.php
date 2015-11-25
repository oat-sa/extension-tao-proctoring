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

/**
 * Base proctoring interface controller
 * 
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
interface ProctoringInterface
{
    /**
     * Get the requested test center resource
     * Use this to identify which test center is currently being selected buy the proctor
     *
     * @return core_kernel_classes_Resource
     * @throws \common_Exception
     */
    function getCurrentTestCenter();

    /**
     * Get the requested delivery resource
     * Use this to identify which delivery is currently being selected buy the proctor
     * 
     * @return core_kernel_classes_Resource
     * @throws \common_Exception
     */
    function getCurrentDelivery();

    /**
     * Main method to render a view for all proctoring related controller actions
     * 
     * @param string $cssClass
     * @param array $data
     * @param array $breadcrumbs
     * @param String $template
     */
    function composeView($cssClass, $data = array(), $breadcrumbs = array(), $template = '');

    /**
     * Gets the data table request options
     *
     * @return array
     */
    function getRequestOptions();
}
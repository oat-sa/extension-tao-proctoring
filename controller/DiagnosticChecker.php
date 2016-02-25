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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\controller;

use oat\taoClientDiagnostic\controller\CompatibilityChecker;
use oat\taoProctoring\model\DiagnosticStorage;

/**
 * Class CompatibilityChecker
 * @package oat\taoClientDiagnostic\controller
 */
class DiagnosticChecker extends CompatibilityChecker
{
    /**
     * Fetch POST data
     * Get login by cookie
     * Get Ip
     * If check parameters is true, check mandatory parameters
     *
     * @param bool $check
     * @return array
     * @throws \common_exception_MissingParameter
     */
    protected function getData($check = false)
    {
        $data = parent::getData($check);

        if ($this->hasRequestParameter('testCenter')) {
            $data[DiagnosticStorage::DIAGNOSTIC_TEST_CENTER] = $this->getRequestParameter('testCenter');
        }
        if ($this->hasRequestParameter('workstation')) {
            $data[DiagnosticStorage::DIAGNOSTIC_WORKSTATION] = $this->getRequestParameter('workstation');
        }

        return $data;
    }
}
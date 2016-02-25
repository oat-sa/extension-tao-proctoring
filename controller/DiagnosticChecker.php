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

use oat\tao\helpers\UserHelper;
use oat\taoClientDiagnostic\controller\CompatibilityChecker;
use oat\taoProctoring\helpers\TestCenterHelper;
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

        $data['login'] = UserHelper::getUserLogin(\common_session_SessionManager::getSession()->getUser());

        if ($this->hasRequestParameter('testCenter')) {
            $data[DiagnosticStorage::DIAGNOSTIC_TEST_CENTER] = $this->getRequestParameter('testCenter');
        }
        if ($this->hasRequestParameter('workstation')) {
            $data[DiagnosticStorage::DIAGNOSTIC_WORKSTATION] = trim($this->getRequestParameter('workstation'));
        }

        return $data;
    }

    /**
     * Get cookie id OR create it if doesnt exist
     * @return string
     */
    protected function getId()
    {
        $id = parent::getId();

        // the id is related to the test center to avoid overwrites
        if ($this->hasRequestParameter('testCenter')) {
            $id = md5($id . $this->getRequestParameter('testCenter') . $id);
        }

        return $id;
    }

    /**
     * Gets the name of the workstation being tested
     */
    public function workstation()
    {
        $response = [
            'success' => false
        ];

        if ($this->hasRequestParameter('testCenter')) {
            $testCenter = new \core_kernel_classes_Resource($this->getRequestParameter('testCenter'));
            $id = $this->getId();
            $response['workstation'] = uniqid('workstation-');

            try {
                $diagnostic = TestCenterHelper::getDiagnostic($testCenter, $id);
            } catch (\common_exception_NoImplementation $e) {
                \common_Logger::i("Unable to get the workstation name for $id ($testCenter)");
            }

            if (isset($diagnostic)) {
                $response['success'] = true;
                $workstation = trim($diagnostic[DiagnosticStorage::DIAGNOSTIC_WORKSTATION]);
                if ($workstation) {
                    $response['workstation'] = $workstation;
                }
            }
        }

        $this->returnJson($response);
    }
}
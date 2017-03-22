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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */

namespace oat\taoProctoring\model\implementation;

use oat\taoProctoring\model\ProctorService;
use oat\taoQtiTest\models\runner\QtiRunnerMessageService;
use qtism\runtime\tests\AssessmentTestSession;

/**
 * Class QtiRunnerMessageService
 *
 * Defines a service that will provide messages for the test runner
 *
 * @package oat\taoQtiTest\models
 */
class TestRunnerMessageService extends QtiRunnerMessageService
{
    protected function isProctorAction(AssessmentTestSession $testSession)
    {
        $userRoles = \common_session_SessionManager::getSession()->getUserRoles();
        return in_array(ProctorService::ROLE_PROCTOR, $userRoles);
    }

    /**
     * Gets a message about the paused status of the assessment when a proctor is the sender
     * @param AssessmentTestSession $testSession
     * @return string
     */
    protected function getProctorPausedStateMessage(AssessmentTestSession $testSession)
    {
        return __('The assessment has been suspended. To resume your assessment, please relaunch it and contact your proctor if required.');
    }

    /**
     * Gets a message about the terminated status of the assessment when a proctor is the sender
     * @param AssessmentTestSession $testSession
     * @return string
     */
    protected function getProctorTerminatedStateMessage(AssessmentTestSession $testSession)
    {
        return __('The assessment has been terminated. You cannot interact with it anymore. Please contact your proctor if required.');
    }

    /**
     * Gets a message about the paused status of the assessment
     * @param AssessmentTestSession $testSession
     * @return string
     */
    protected function getPausedStateMessage(AssessmentTestSession $testSession)
    {
        if ($this->isProctorAction($testSession)) {
            return $this->getProctorPausedStateMessage($testSession);
        }

        return __('The assessment has been suspended. To resume your assessment, please relaunch it.');
    }

    /**
     * Gets a message about the terminated status of the assessment
     * @param AssessmentTestSession $testSession
     * @return string
     */
    protected function getTerminatedStateMessage(AssessmentTestSession $testSession)
    {
        if ($this->isProctorAction($testSession)) {
            return $this->getProctorTerminatedStateMessage($testSession);
        }

        return __('The assessment has been terminated. You cannot interact with it anymore.');
    }
}

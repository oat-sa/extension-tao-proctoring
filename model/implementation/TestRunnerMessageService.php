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

use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
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
    /**
     * @var string Controls whether a pause message is not sent on status, default false
     */
    private const FEATURE_FLAG_SKIP_PAUSED_ASSESSMENT_DIALOG = 'FEATURE_FLAG_SKIP_PAUSED_ASSESSMENT_DIALOG';

    /** Proctor roles option in options. */
    const PROCTOR_ROLES_OPTION = 'proctorRoles';

    /**
     * Returns TRUE when the current role is proctor like.
     *
     * @param AssessmentTestSession $testSession
     *
     * @return bool
     *
     * @throws \common_exception_Error
     */
    protected function isProctorAction(AssessmentTestSession $testSession)
    {
        $userRoles = \common_session_SessionManager::getSession()->getUserRoles();
        $proctorRoles = $this->getOption(static::PROCTOR_ROLES_OPTION);

        foreach ($proctorRoles as $proctorRole) {
            if (in_array($proctorRole, $userRoles)) {
                return true;
            }
        }

        return false;
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
        if (false === $this->isPausedDialogRequired()) {
            return '';
        }

        if ($this->isProctorAction($testSession)) {
            return $this->getProctorPausedStateMessage($testSession);
        }

        return parent::getPausedStateMessage($testSession);
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

        return parent::getTerminatedStateMessage($testSession);
    }

    private function isPausedDialogRequired(): bool
    {
        return false === $this->getFeatureFlagChecker()->isEnabled(self::FEATURE_FLAG_SKIP_PAUSED_ASSESSMENT_DIALOG);
    }

    private function getFeatureFlagChecker(): FeatureFlagCheckerInterface
    {
        return $this->getServiceLocator()->get(FeatureFlagChecker::class);
    }
}

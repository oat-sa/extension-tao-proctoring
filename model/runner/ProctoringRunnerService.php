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
 */

namespace oat\taoProctoring\model\runner;

use oat\generis\model\OntologyAwareTrait;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\ProctorService;
use oat\taoQtiTest\models\runner\QtiRunnerPausedException;
use oat\taoQtiTest\models\runner\QtiRunnerService;
use oat\taoQtiTest\models\runner\RunnerServiceContext;
use qtism\runtime\tests\AssessmentTestSessionState;

/**
 * Class ProctoringRunnerService
 *
 * QTI implementation service for the test runner
 *
 * @package oat\taoProctoring\model\runner
 */
class ProctoringRunnerService extends QtiRunnerService
{
    use OntologyAwareTrait;

    /**
     * Get Test Context.
     *
     * @param RunnerServiceContext $context
     * @return array
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function getTestContext(RunnerServiceContext $context)
    {
        $response = parent::getTestContext($context);

        if (isset($response['options']) && isset($response['options']['sectionPause'])) {
            $response['securePauseStateRequired'] = $response['options']['sectionPause'];
        } else {
            if ($context->getTestExecutionUri()) {
                $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($context->getTestExecutionUri());
                if ($this->isProctoredDelivery($deliveryExecution->getDelivery())) {
                    $response['securePauseStateRequired'] = true;
                }
            }
        }

        return $response;
    }

    /**
     * Check whether secure plugins must be used.
     *
     * @param \core_kernel_classes_Resource $delivery
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    private function isProctoredDelivery(\core_kernel_classes_Resource $delivery)
    {
        $hasProctor = $delivery->getOnePropertyValue($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR));
        $result = $hasProctor instanceof \core_kernel_classes_Resource &&
            $hasProctor->getUri() == ProctorService::ACCESSIBLE_PROCTOR_ENABLED;
        return $result;
    }

    /**
     * Check whether the test is in a runnable state.
     *
     * @param RunnerServiceContext $context
     * @return bool
     * @throws \common_Exception
     * @throws \oat\taoQtiTest\models\runner\QtiRunnerClosedException
     * @throws QtiRunnerPausedException
     */
    public function check(RunnerServiceContext $context)
    {
        parent::check($context);

        $state = $context->getTestSession()->getState();

        if ($state == AssessmentTestSessionState::SUSPENDED) {
            throw new QtiRunnerPausedException();
        }

        return true;
    }
}

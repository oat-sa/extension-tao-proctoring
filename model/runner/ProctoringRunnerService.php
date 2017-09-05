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
use oat\oatbox\event\EventManager;
use oat\taoAct\model\event\HeartbeatStored;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\ProctorService;
use oat\taoQtiTest\models\runner\QtiRunnerService;
use oat\taoQtiTest\models\runner\QtiRunnerServiceContext;
use oat\taoQtiTest\models\runner\RunnerServiceContext;
use oat\taoQtiTest\models\TestSessionMetaData;

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

    public function getTestContext(RunnerServiceContext $context)
    {
        $response = parent::getTestContext($context);

        if ($context->getTestExecutionUri()) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($context->getTestExecutionUri());
            if ($this->isProctoredDelivery($deliveryExecution->getDelivery())) {
                $response['securePauseStateRequired'] = true;
            }
        }
        return $response;
    }

    /**
     * Check whether secure plugins must be used.
     * @param \core_kernel_classes_Resource $delivery
     * @return bool
     */
    private function isProctoredDelivery(\core_kernel_classes_Resource $delivery)
    {
        $hasProctor = $delivery->getOnePropertyValue($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR));
        $result = $hasProctor instanceof \core_kernel_classes_Resource &&
            $hasProctor->getUri() == ProctorService::ACCESSIBLE_PROCTOR_ENABLED;
        return $result;
    }
}

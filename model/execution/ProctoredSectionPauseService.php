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
 * @author Alexander Zagovorychev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model\execution;


use oat\generis\model\OntologyAwareTrait;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;
use oat\taoQtiTest\models\SectionPauseService;
use oat\taoQtiTest\models\runner\session\TestSession;
use qtism\data\AssessmentItemRef;
use qtism\runtime\tests\AssessmentTestSessionState;

class ProctoredSectionPauseService extends SectionPauseService
{
    use OntologyAwareTrait;

    /**
     * Checked that section can be paused
     * @param TestSession $session
     * @return bool
     */
    public function isPausable(TestSession $session = null)
    {
        $isPausable = false;
        if ($session->getState() === AssessmentTestSessionState::INTERACTING) {
            /** @var AssessmentItemRef $itemRef */
            $itemRef = $session->getCurrentAssessmentItemRef();
            $user = \common_session_SessionManager::getSession()->getUser();
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($session->getSessionId());
            $isPausable = $this->getServiceManager()->get(TestTakerAuthorizationService::SERVICE_ID)->isProctored($deliveryExecution->getDelivery(), $user)
                && $itemRef->getCategories()->contains('x-tao-proctored-auto-pause');
        }
        return $isPausable;
    }
}

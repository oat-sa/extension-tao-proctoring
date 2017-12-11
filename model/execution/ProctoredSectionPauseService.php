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
     * This category triggers the section pause
     */
    const PAUSE_CATEGORY = 'x-tao-proctored-auto-pause';

    private $isProctored = null;

    /**
     * Checked the given session could be paused at some point
     * (in other words : is section pause enabled)
     * @param $session
     * @return bool
     */
    public function couldBePaused(TestSession $session = null)
    {
        return ($session->getState() === AssessmentTestSessionState::INTERACTING && $this->isProctored($session));
    }

    /**
     * Checked that section can be paused
     * @param TestSession $session
     * @return bool
     */
    public function isPausable(TestSession $session = null)
    {
        if ($this->couldBePaused($session)) {

            /** @var AssessmentItemRef $itemRef */
            $itemRef = $session->getCurrentAssessmentItemRef();

            return $this->isItemPausable($itemRef);
        }
        return false;
    }

    /**
     * Check if we can move backward : when leaving a pausable section,
     * we can't move backward.
     *
     * @param TestSession $session
     * @return bool
     */
    public function canMoveBackward(TestSession $session = null)
    {
        if ($this->couldBePaused($session)) {
            return ! $this->isItemPausable($session->getCurrentAssessmentItemRef());
        }
        return true;
    }

    /**
     * Is the given section proctored
     *
     * @param TestSession $session
     * @return bool false by default
     */
    private function isProctored(TestSession $session)
    {
        //check only once
        if (is_null($this->isProctored)) {
            $this->isProctored = false;

            if (!is_null($session)) {
                $user = \common_session_SessionManager::getSession()->getUser();
                $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($session->getSessionId());
                $this->isProctored = $this->getServiceManager()->get(TestTakerAuthorizationService::SERVICE_ID)->isProctored($deliveryExecution->getDelivery(), $user);
            }

        }
        return $this->isProctored;
    }


    /**
     * Is the given itemRef pauseable (ie. has the given category)
     *
     * @param AssessmentItemRef $itemRef
     * @return bool false by default
     */
    private function isItemPausable(AssessmentItemRef $itemRef)
    {
        if (!is_null($itemRef)) {
            return $itemRef->getCategories()->contains(self::PAUSE_CATEGORY);
        }
        return false;
    }
}

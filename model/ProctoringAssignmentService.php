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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */
namespace oat\taoProctoring\model;

use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\oatbox\user\User;
use oat\taoGroups\models\GroupsService;

/**
 * @access public
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package taoProctoring
 */
class ProctoringAssignmentService extends GroupAssignment
{
    /**
     * @inheritdoc
     */
    public function getDeliveryIdsByUser(User $user)
    {
        $deliveryUris = array();
        // check if really available
        foreach (GroupsService::singleton()->getGroups($user) as $group) {
            foreach ($group->getPropertyValues(new \core_kernel_classes_Property(PROPERTY_GROUP_DELVIERY)) as $deliveryUri) {
                $candidate = new \core_kernel_classes_Resource($deliveryUri);
                if (!$this->isUserExcluded($candidate, $user) && $candidate->exists() && $this->isEligible($candidate, $user)) {
                    $deliveryUris[] = $candidate->getUri();
                }
            }
        }
        return array_unique($deliveryUris);
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     */
    protected function isEligible(\core_kernel_classes_Resource $delivery, User $user)
    {
        $result = false;
        $class = new \core_kernel_classes_Class(EligibilityService::CLASS_URI);
        $eligibilities = $class->searchInstances([
            EligibilityService::PROPERTY_TESTTAKER_URI => $user->getIdentifier(),
            EligibilityService::PROPERTY_DELIVERY_URI => $delivery->getUri(),
        ]);

        foreach ($eligibilities as $eligibility) {
            /* @var \core_kernel_classes_Resource $eligibility*/
            $testCenter = $eligibility->getOnePropertyValue(new \core_kernel_classes_Property(EligibilityService::PROPERTY_TESTCENTER_URI));
            if ($testCenter instanceof \core_kernel_classes_Resource && $testCenter->exists()) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
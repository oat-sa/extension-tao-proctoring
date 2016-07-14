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
namespace oat\taoProctoring\model;

use \core_kernel_classes_Resource as Resource;
use core_kernel_classes_Class;
use \core_kernel_classes_Property as Property;
use oat\oatbox\event\EventManager;
use oat\taoProctoring\model\event\EligiblityChanged;
use oat\taoProctoring\model\implementation\DeliveryService;
use tao_models_classes_ClassService;
use oat\oatbox\user\User;

/**
 * Service to manage eligible deliveries
 */
class EligibilityService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#DeliveryEligibility';

    const PROPERTY_TESTCENTER_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestCenter';

    const PROPERTY_TESTTAKER_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestTaker';

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileDelivery';

    const PROPERTY_BYPASSPROCTOR_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ByPassProctor"';

    const BOOLEAN_TRUE = 'http://www.tao.lu/Ontologies/generis.rdf#True';

    const BOOLEAN_FALSE = 'http://www.tao.lu/Ontologies/generis.rdf#False';

    /**
     * return the test center top level class
     *
     * @access public
     * @return core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return new core_kernel_classes_Class(self::CLASS_URI);
    }
    
    /**
     * Establishes a new eligibility
     * 
     * @param Resource $testCenter
     * @param Resource $delivery
     * @return boolean
     */
    public function createEligibility(Resource $testCenter, Resource $delivery) {
        if (!is_null($this->getEligibility($testCenter, $delivery))) {
            // already exists, don't recreate
            return false;
        }
        $eligibilty = $this->getRootClass()->createInstanceWithProperties(array(
            self::PROPERTY_TESTCENTER_URI => $testCenter,
            self::PROPERTY_DELIVERY_URI => $delivery
        ));
        return true;
    }

    /**
     * Get deliveries eligible at a testcenter
     *
     * @param Resource $testCenter
     * @param bool $sort
     * @return \Resource[]
     */
    public function getEligibleDeliveries(Resource $testCenter, $sort = true) {
        $eligibles = $this->getRootClass()->searchInstances(array(
            self::PROPERTY_TESTCENTER_URI => $testCenter
        ), array('recursive' => false, 'like' => false));

        $deliveryProperty = new Property(self::PROPERTY_DELIVERY_URI);
        
        $deliveries = array();
        foreach ($eligibles as $eligible) {
            $delivery = $eligible->getOnePropertyValue($deliveryProperty);
            if ($delivery->exists()) {
                $deliveries[] = $delivery;
            }
        }

        if ($sort) {
            usort($deliveries, function ($a, $b) {
                return strcmp($a->getLabel(), $b->getLabel());
            });
        }

        return $deliveries;
    }

    /**
     * Get eligibilities of a  test center
     *
     * @param Resource $testCenter
     * @param array options paginantion options
     * @return array formated eligibilities
     */
    public function getEligibilities(Resource $testCenter, $options = []) {

        $eligibilities = [];

        $eligibles = $this->getRootClass()->searchInstances(array(
            self::PROPERTY_TESTCENTER_URI => $testCenter
        ), array('recursive' => false, 'like' => false));

        $deliveryProperty  = new Property(self::PROPERTY_DELIVERY_URI);
        $byPassProperty    = new Property(self::PROPERTY_BYPASSPROCTOR_URI);
        $testTakerProperty = new Property(self::PROPERTY_TESTTAKER_URI);

        foreach ($eligibles as $eligible) {
            $values = $eligible->getPropertiesValues([$deliveryProperty, $byPassProperty, $testTakerProperty]);

            $delivery = current($values[self::PROPERTY_DELIVERY_URI]);
            if ($delivery->exists()) {
                $byPass = current($values[self::PROPERTY_BYPASSPROCTOR_URI]);

                $eligibilities[] = [
                    'uri' => $eligible->getUri(),
                    'delivery' => [
                        'uri' => $delivery->getUri(),
                        'label' => $delivery->getLabel()
                    ],
                    'byPassProctor' => $byPass instanceof \core_kernel_classes_Resource ? $byPass->getUri() == self::BOOLEAN_TRUE : false,
                    'testTakers' => array_map(function($testTaker) {
                        return [
                            'uri' => $testTaker->getUri(),
                            'label' => $testTaker->getLabel()
                        ];
                    }, $values[self::PROPERTY_TESTTAKER_URI])
                ];
            }
        }

        if ($options['sort'] == true) {
            usort($eligibilities, function ($comparedA, $comparedB) {
                return strcmp($comparedA['delivery']['label'], $comparedB['delivery']['label']);
            });
        }

        return $eligibilities;
    }

    /**
     * Removes an eligibility
     * 
     * @param Resource $testCenter
     * @param Resource $delivery
     * @throws IneligibileException
     * @return boolean
     */
    public function removeEligibility(Resource $testCenter, Resource $delivery) {
        $eligibility = $this->getEligibility($testCenter, $delivery);
        if (is_null($eligibility)) {
            throw new IneligibileException('Delivery '.$delivery->getUri().' ineligible to test center '.$testCenter->getUri());
        }
        $deletion = $eligibility->delete();
        if($deletion){
            //unassign all test taker for this delivery in this test center
            $deliveryService = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
            $deliveryService->removeAvailability($delivery->getUri(), $testCenter->getUri());
        }
        return $deletion;
    }
    
    /**
     * Return ids of test-takers that are eligble in the specified context
     * 
     * @param Resource $testCenter
     * @param Resource $delivery
     * @return string[] identifiers of the test-takers
     */
    public function getEligibleTestTakers(Resource $testCenter, Resource $delivery) {
        $eligible = array();
        $eligibility = $this->getEligibility($testCenter, $delivery);
        if (!is_null($eligibility)) {
            foreach ($eligibility->getPropertyValues(new Property(self::PROPERTY_TESTTAKER_URI)) as $testTaker) {
                $eligible[] = $testTaker instanceof Resource ? $testTaker->getUri() : (string)$testTaker;
            }
        }
        return $eligible;
    }
    
    /**
     * Allow test-taker to be eligible for this testcenter/delivery context
     *
     * @param Resource $testCenter
     * @param Resource $delivery
     * @param string[] $testTakerIds
     * @throws IneligibileException
     * @return boolean
     */
    public function setEligibleTestTakers(Resource $testCenter, Resource $delivery, $testTakerIds) {
        /** @var \core_kernel_classes_Resource $eligibility */
        $eligibility = $this->getEligibility($testCenter, $delivery);
        if (is_null($eligibility)) {
            throw new IneligibileException('Delivery '.$delivery->getUri().' ineligible to test center '.$testCenter->getUri());
        }

        $previousTestTakerCollection = $eligibility->getPropertyValues(new Property(self::PROPERTY_TESTTAKER_URI));

        $result =  $eligibility->editPropertyValues(new Property(self::PROPERTY_TESTTAKER_URI), $testTakerIds);

        $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);
        $eventManager->trigger(new EligiblityChanged($eligibility, $previousTestTakerCollection, $testTakerIds));

        return $result;
    }
    
    /**
     * Returns the eligibility representing the link, or null if not found
     *  
     * @param Resource $testCenter
     * @param Resource $delivery
     * @throws \common_exception_InconsistentData
     * @return null|Resource eligibility resource
     */
    public function getEligibility(Resource $testCenter, Resource $delivery) {
        $eligibles = $this->getRootClass()->searchInstances(array(
            self::PROPERTY_TESTCENTER_URI => $testCenter,
            self::PROPERTY_DELIVERY_URI => $delivery
        ), array('recursive' => false, 'like' => false));
        if (count($eligibles) == 0) {
            return null;
        }
        if (count($eligibles) > 1) {
            throw new \common_exception_InconsistentData('Multiple eligibilities for testcenter '.$testCenter->getUri().' and delivery '.$delivery->getUri());
        }
        return reset($eligibles);
    }

    /**
     * @param Resource $delivery
     * @param User $user
     * @return bool
     */
    public function isDeliveryEligible(Resource $delivery, User $user)
    {
        return null !== $this->getTestCenter($delivery, $user);
    }

    /**
     * @param Resource $delivery
     * @param User $user
     * @return \core_kernel_classes_Container|Resource|null
     * @throws \core_kernel_persistence_Exception
     */
    public function getTestCenter(Resource $delivery, User $user){
        $result = null;
        $class = new \core_kernel_classes_Class(EligibilityService::CLASS_URI);
        $eligibilities = $class->searchInstances([
            EligibilityService::PROPERTY_TESTTAKER_URI => $user->getIdentifier(),
            EligibilityService::PROPERTY_DELIVERY_URI => $delivery->getUri(),
        ]);

        foreach ($eligibilities as $eligibility) {
            /* @var \core_kernel_classes_Resource $eligibility*/
            $testCenter = $eligibility->getOnePropertyValue(new \core_kernel_classes_Property(EligibilityService::PROPERTY_TESTCENTER_URI));
            if ($testCenter instanceof \core_kernel_classes_Resource && $testCenter->exists()) {
                $result = $testCenter;
                break;
            }
        }

        return $result;
    }

    /**
     * @param Resource $eligibility
     * @return \core_kernel_classes_Resource
     * @throws \core_kernel_persistence_Exception
     */
    public function getDelivery(Resource $eligibility)
    {
        /* @var \core_kernel_classes_Resource $eligibility */
        $delivery = $eligibility->getOnePropertyValue(new \core_kernel_classes_Property(EligibilityService::PROPERTY_DELIVERY_URI));
        return $delivery;

    }

    /**
     * Check whether this Eligibility can by-pass the proctor authorization
     * @param Resource $eligibility
     * @return boolean true if the elligility can by-pass the proctor authorization
     */
    public function canByPassProctor(Resource $eligibility)
    {
        $canByPass = $eligibility->getOnePropertyValue(new Property(self::PROPERTY_BYPASSPROCTOR_URI));
        return !is_null($canByPass) ? ($canByPass->getUri() == self::BOOLEAN_TRUE) : false;    
    }

    /**
     * Set whether this Eligibility can by-pass the proctor authorization
     * @param Resource $eligibility
     * @param boolean $bypass true if the elligility can by-pass the proctor authorization
     */
    public function setByPassProctor(Resource $eligibility, $bypass = false)
    {
        $eligibility->editPropertyValues(new Property(self::PROPERTY_BYPASSPROCTOR_URI), new Resource($bypass ? self::BOOLEAN_TRUE : self::BOOLEAN_FALSE));
    }
}

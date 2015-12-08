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

use oat\oatbox\user\User;
use oat\taoTestTaker\models\TestTakerService;
use core_kernel_classes_Resource as Resource;
use core_kernel_classes_Class;
use core_kernel_classes_Property as Property;
use tao_models_classes_ClassService;
use taoDelivery_models_classes_DeliveryRdf;

/**
 * Service to manage eligible deliveries
 */
class EligibilityService extends tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#DeliveryEligibility';

    const PROPERTY_TESTCENTER_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestCenter';

    const PROPERTY_TESTTAKER_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestTaker';

    const PROPERTY_DELIVERY_URI = 'http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileDelivery';

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
     * @return bool
     */
    public function createEligibility(Resource $testCenter, Resource $delivery) {
        // verify it doesn't exist yet
        $eligibilty = $this->getRootClass()->createInstanceWithProperties(array(
            self::PROPERTY_TESTCENTER_URI => $testCenter,
            self::PROPERTY_DELIVERY_URI => $delivery,
            RDFS_LABEL => $delivery->getLabel()
        ));
        return true;
    }
    
    /**
     * Get deliveries eligible at a testcenter
     * 
     * @param Resource $testCenter
     * @return Resource[]
     */
    public function getEligibleDeliveries(Resource $testCenter) {
        return array();
    }
    
    /**
     * Removes an eligibility
     * 
     * @param Resource $eligibility
     * @return bool
     */
    public function removeEligibility(Resource $testCenter, Resource $delivery) {
        
        return $eligibility->delete();
    }
    
    /**
     * Return ids of test-takers that are eligble in the specified context
     * 
     * @param Resource $testCenter
     * @param Resource $delivery
     * @return string[] identifiers of the test-takers
     */
    public function getEligibleTestTakers(Resource $testCenter, Resource $delivery) {
        //$found = $this->getRootClass()->searchInstances(array(), array());
        return array();
    }
    
    /**
     * Allow test-taker to be eligible for this testcenter/delivery context
     *
     * @param Resource $eligibility
     * @param string[] $testTakerIds
     * @return bool
     */
    public function setEligibleTestTakers(Resource $testCenter, Resource $delivery, $testTakerIds) {
        return $eligibility->editPropertyValues(new Property(self::PROPERTY_TESTTAKER_URI). $testTakerIds);
    }
    
    /**
     * Returns the eligibility representing the link, or null if not found
     *  
     * @param Resource $testCenter
     * @param Resource $delivery
     * @return Resource eligibility resource
     */
    protected function getEligiblity(Resource $testCenter, Resource $delivery) {
        //$found = $this->getRootClass()->searchInstances(array(), array());
        return null;
    }
    
}

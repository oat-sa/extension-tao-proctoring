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
 */

namespace oat\taoProctoring\model\event;

use core_kernel_classes_Resource;

use oat\oatbox\event\Event;

class EligiblityChanged implements Event
{

    const EVENT_NAME = __CLASS__;

    /**
     * @var core_kernel_classes_Resource
     */
    private $eligiblity;

    /**
     * @var string[]
     */
    private $previousTestTakerCollection;
    /**
     * @var string[]
     */
    private $actualTestTakersCollection;

    /**
     * @return string
     */
    public function getName()
    {
        return self::EVENT_NAME;
    }

    /**
     * DeliveryExecutionExpired constructor.
     * @param core_kernel_classes_Resource $eligiblity
     * @param string[] $previousTestTakerCollection
     * @param string[] $actualTestTakersCollection optional, should improve performance to reduce calculations
     */
    public function __construct(core_kernel_classes_Resource $eligiblity, array $previousTestTakerCollection , array $actualTestTakersCollection = [])
    {
        $this->eligiblity = $eligiblity;
        $this->previousTestTakerCollection = $previousTestTakerCollection;
        $this->actualTestTakersCollection = $actualTestTakersCollection;
    }

    /**
     * Returns the eligiblity
     *
     * @return core_kernel_classes_Resource
     */
    public function getEligiblity()
    {
        return $this->eligiblity;
    }

    /**
     * @return \string[]
     */
    public function getActualTestTakersCollection()
    {
        return $this->actualTestTakersCollection;
    }

    /**
     * @return \string[]
     */
    public function getPreviousTestTakerCollection()
    {
        return $this->previousTestTakerCollection;
    }
}
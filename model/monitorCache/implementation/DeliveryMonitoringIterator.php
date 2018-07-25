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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */
namespace oat\taoProctoring\model\monitorCache\implementation;

use Iterator;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class DeliveryMonitoringIterator
 * @package oat\taoProctoring\model\monitorCache\implementation
 */
class DeliveryMonitoringIterator implements \Iterator
{
    use ServiceLocatorAwareTrait;

    const CACHE_SIZE = 10000;

    /**
     * Id of the current instance
     *
     * @var int
     */
    private $currentInstance = 0;

    /**
     * List of resource uris currently being iterated over
     *
     * @var array
     */
    private $instanceCache = null;

    /**
     * Indicater whenever the end of  the current cache is also the end of the current class
     *
     * @var boolean
     */
    private $endOfResource = false;

    /**
     * Whenever we already moved the pointer, used to prevent unnecessary rewinds
     *
     * @var boolean
     */
    private $unmoved = true;

    /**
     * DeliveryMonitoringIterator constructor.
     * @param ServiceLocatorInterface $serviceLocator
     * @throws \common_exception_Error
     */
    public function __construct(ServiceLocatorInterface $serviceLocator) {
        $this->setServiceLocator($serviceLocator);
        $this->load(0);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::rewind()
     */
    function rewind() {
        if (!$this->unmoved) {
            $this->unmoved = true;
        }
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::current()
     */
    function current() {
        return $this->instanceCache[$this->currentInstance];
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::key()
     */
    function key() {
        return $this->currentInstance;
    }


    function valid()
    {
        return isset($this->instanceCache[$this->currentInstance]);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::next()
     */
    function next() {
        $this->unmoved = false;
        if ($this->valid()) {
            $this->currentInstance++;
            if (!isset($this->instanceCache[$this->currentInstance])) {
                // try to load next block (unless we know it's empty)
                $remainingInstances = !$this->endOfResource && $this->load($this->currentInstance);
            }
        }
    }

    /**
     * @param $offset
     * @return bool
     */
    protected function load($offset)
    {
        \common_Logger::d(__CLASS__ . '::load offset = ' . $offset);

        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
        $options = array(
            'offset' => $offset,
            'limit' => self::CACHE_SIZE
        );

        $executions = $deliveryMonitoringService->find([], $options);

        $this->instanceCache = array();

        foreach ($executions as $execution) {
            $this->instanceCache[$offset] = $execution->get();
            $offset++;
        }

        $this->endOfResource = count($executions) < self::CACHE_SIZE;

        return count($executions) > 0;
    }
}
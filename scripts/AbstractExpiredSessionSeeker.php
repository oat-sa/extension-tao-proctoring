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
 *
 */

namespace oat\taoProctoring\scripts;

use oat\oatbox\action\Action;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use common_report_Report as Report;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

/**
 * Abstract script to search expired sessions
 */
abstract class AbstractExpiredSessionSeeker implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Report
     */
    protected $report;

    /**
     * @param array $params
     * @return Report
     */
    abstract public function __invoke($params);

    /**
     * Checks if delivery execution was expired after pausing
     *
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws
     */
    abstract protected function isExpired(DeliveryExecution $deliveryExecution);

    /**
     * @param $type
     * @param string $message
     * @throws
     */
    protected function addReport($type, $message)
    {
        $this->report->add(new Report(
            $type,
            $message
        ));
    }
    
    /**
     * Get last test takers event from delivery log
     * @param DeliveryExecution $deliveryExecution
     * @return array|null
     * @throws
     */
    protected function getLastTestTakersEvent(DeliveryExecution $deliveryExecution)
    {
        $deliveryLogService = $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
        $testTakerIdentifier = $deliveryExecution->getUserIdentifier();
        $events = array_reverse($deliveryLogService->get($deliveryExecution->getIdentifier()));

        $lastTestTakersEvent = null;
        foreach ($events as $event) {
            if ($event[DeliveryLog::CREATED_BY] === $testTakerIdentifier) {
                $lastTestTakersEvent = $event;
                break;
            }
        }

        return $lastTestTakersEvent;
    }

}

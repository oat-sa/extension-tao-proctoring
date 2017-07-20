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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model;


use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;

/**
 * Service which allows to use many proctorServices according to condition
 * Class ProctorServiceDelegator
 * @package oat\taoProctoring\model
 */
class ProctorServiceDelegator extends ConfigurableService implements ProctorServiceInterface
{
    /**
     * Services which could handle the request
     */
    const PROCTOR_SERVICE_HANDLERS = 'handlers';

    /**
     * @var ProctorService
     */
    private $extendedService;

    /**
     * Returns applicable ProctorServices
     *
     * @throws \common_exception_NoImplementation
     * @return \oat\taoProctoring\model\ProctorService
     */
    public function getResponsibleService()
    {
        if (!isset($this->extendedService))
        {
            foreach ($this->getOption(self::PROCTOR_SERVICE_HANDLERS) as $handler) {
                if (!is_a($handler, ProctorServiceHandler::class)) {
                    throw new \common_exception_NoImplementation('Handler should be instance of ProctorServiceHandler.');
                }
                $handler->setServiceLocator($this->getServiceLocator());
                if ($handler->isSuitable()) {
                    $this->extendedService = $handler;
                    break;
                }
            }
        }
        return $this->extendedService;
    }

    public function registerHandler($handler)
    {
        $handlers = $this->getOption(self::PROCTOR_SERVICE_HANDLERS);
        $exists = false;

        // change options on the existing handlers
        foreach ($handlers as $key => $_handler) {
            if ($_handler instanceof $handler) {
                $handlers[$key] = $handler;
                $exists = true;
                break;
            }
        }

        // new handler should be added to the top of the list
        if (!$exists) {
            $handlers = array_merge([$handler], $handlers);
        }

        $this->setOption(self::PROCTOR_SERVICE_HANDLERS, $handlers);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::getProctorableDeliveries()
     */
    public function getProctorableDeliveries(User $proctor, $context = null)
    {
        return $this->getResponsibleService()->getProctorableDeliveries($proctor, $context);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::getProctorableDeliveryExecutions()
     */
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        return $this->getResponsibleService()->getProctorableDeliveryExecutions($proctor, $delivery, $context, $options);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoProctoring\model\ProctorServiceInterface::countProctorableDeliveryExecutions()
     */
    public function countProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = [])
    {
        return $this->getResponsibleService()->countProctorableDeliveryExecutions($proctor, $delivery, $context, $options);
    }
}

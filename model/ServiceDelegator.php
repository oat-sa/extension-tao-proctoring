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

abstract class ServiceDelegator extends ConfigurableService implements ServiceDelegatorInterface
{

    /**
     * @var ConfigurableService
     */
    private $service;

    /**
     * Returns applicable service
     *
     * @throws \common_exception_NoImplementation
     * @param $user
     * @param $deliveryId
     * @return DelegatedServiceHandler
     */
    public function getResponsibleService(User $user, $deliveryId = null)
    {
        if (!isset($this->service))
        {
            /** @var DelegatedServiceHandler $handler */
            foreach ($this->getOption(self::SERVICE_HANDLERS) as $handler) {
                if (!is_a($handler, DelegatedServiceHandler::class)) {
                    throw new \common_exception_NoImplementation('Handler should be instance of DelegatorServiceHandler.');
                }
                $handler->setServiceLocator($this->getServiceLocator());
                if ($handler->isSuitable($user, $deliveryId)) {
                    $this->service = $handler;
                    break;
                }
            }
        }
        return $this->service;
    }

    /**
     * @param $handler
     */
    public function registerHandler(DelegatedServiceHandler $handler)
    {
        $handlers = $this->getOption(self::SERVICE_HANDLERS);
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

        $this->setOption(self::SERVICE_HANDLERS, $handlers);
    }

}

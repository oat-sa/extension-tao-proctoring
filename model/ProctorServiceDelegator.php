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
     * Options for the proctor services
     */
    const PROCTOR_SERVICE_OPTIONS = 'options';

    /**
     * @var ProctorService
     */
    private $extendedService;

    /**
     * ProctorServiceRoute constructor.
     * @param array $options
     * @throws \common_exception_NoImplementation
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ($this->hasOption(self::PROCTOR_SERVICE_HANDLERS)) {
            $handlers = $this->getOption(self::PROCTOR_SERVICE_HANDLERS);
            foreach ($handlers as $handler) {
                if (!class_exists($handler)) {
                    throw new \common_exception_NoImplementation('Invalid configuration of the ProctorServiceDelegator.');
                }

                /** @var ProctorService $handlerService */
                $options = $this->hasOption(self::PROCTOR_SERVICE_OPTIONS) ?
                    $this->getOption(self::PROCTOR_SERVICE_OPTIONS) : [];

                $handlerService = new $handler($options);
                if (!is_a($handlerService, ProctorService::class)) {
                    throw new \common_exception_NoImplementation('Handler should be instance of ProctorService. Property serviceClass in the configuration of the ProctorServiceDelegator is incorrect');
                }

                if ($handlerService->isSuitable()) {
                    $this->extendedService = $handlerService;
                    break;
                }
            }
        }

        if (!$this->extendedService) {
            $this->extendedService = new ProctorService($options);
        }
    }

    /**
     * Delegate request to the responsible service
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->extendedService->setServiceManager($this->getServiceManager());
        return call_user_func_array([$this->extendedService, $name], $arguments);
    }
}

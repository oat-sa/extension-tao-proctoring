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
 * Class ProctorServiceRoute
 * @package oat\taoProctoring\model
 */
class ProctorServiceRoute extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/ProctorAccess';

    const PROCTOR_SERVICE_ROUTES = 'routes';

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

        if ($this->hasOption(self::PROCTOR_SERVICE_ROUTES)) {
            $routes = $this->getOption(self::PROCTOR_SERVICE_ROUTES);
            foreach ($routes as $route) {
                if (!class_exists($route)) {
                    throw new \common_exception_NoImplementation('Invalid configuration of the ProctorServiceRoute.');
                }
                /** @var ProctorService $routeService */
                $routeService = new $route($options);
                if (!is_a($routeService, ProctorService::class)) {
                    throw new \common_exception_NoImplementation('RouteService should be instance of ProctorService. Property serviceClass in the configuration of the ProctorServiceRoute is incorrect');
                }

                if ($routeService->isSuitable()) {
                    $this->extendedService = $routeService;
                    break;
                }
            }
        }

        if (!$this->extendedService) {
            $this->extendedService = new ProctorService($options);
        }
    }

    /**
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

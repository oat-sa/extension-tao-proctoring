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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\model\breadcrumbs;


use oat\oatbox\service\ConfigurableService;
use oat\tao\model\mvc\Breadcrumbs;
use oat\taoProctoring\model\ProctorService;

/**
 * Provides breadcrumbs for the Monitor controller.
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
class MonitorService extends ConfigurableService implements Breadcrumbs
{
    const SERVICE_ID = 'taoProctoring/Monitor/breadcrumbs';

    /**
     * Builds breadcrumbs for a particular route.
     * @param string $route - The route URL
     * @param array $parsedRoute - The parsed URL (@see parse_url), augmented with extension, controller and action
     * @return array|null - The breadcrumb related to the route, or `null` if none. Must contains:
     * - id: the route id
     * - url: the route url
     * - label: the label displayed for the breadcrumb
     * - entries: a list of related links, using the same format as above
     */
    public function breadcrumbs($route, $parsedRoute)
    {
        if (isset($parsedRoute['action'])) {
            switch ($parsedRoute['action']) {
                case 'index':
                    return $this->breadcrumbsIndex($route, $parsedRoute);
            }
        }
        return null;
    }

    /**
     * Gets the breadcrumbs for the index page
     * @param string $route
     * @param array $parsedRoute
     * @return array
     */
    protected function breadcrumbsIndex($route, $parsedRoute)
    {
        $routeContext = null;
        $routeDelivery = null;
        
        if (isset($parsedRoute['params'])) {
            if (isset($parsedRoute['params']['delivery'])) {
                $routeDelivery = $parsedRoute['params']['delivery'];
            }
            if (isset($parsedRoute['params']['context'])) {
                $routeContext = $parsedRoute['params']['context'];
            }
        }

        $service = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $deliveries = $service->getProctorableDeliveries($proctor, $routeContext);
        $entries = array();
        $main = null;
        foreach ($deliveries as $delivery) {
            $deliveryId = $delivery->getUri();
            $crumb = [
                'id' => $deliveryId,
                'url' => _url('index', 'Monitor', 'taoProctoring', ['delivery' => $deliveryId, 'context' => $routeContext]),
                'label' => $delivery->getLabel(),
            ];
            
            if ($deliveryId == $routeDelivery) {
                $main = $crumb;
            } else {
                $entries[] = $crumb;
            }
        }
        
        if ($main) {
            $main['entries'] = $entries;
        }

        return $main;
    }
}

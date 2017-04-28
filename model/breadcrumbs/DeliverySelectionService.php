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

/**
 * Provides breadcrumbs for the DeliverySelection controller.
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
class DeliverySelectionService extends ConfigurableService implements Breadcrumbs
{
    const SERVICE_ID = 'taoProctoring/DeliverySelection/breadcrumbs';
    
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
            switch($parsedRoute['action']) {
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
        $urlContext = [];
        if (isset($parsedRoute['params'])) {
            if (isset($parsedRoute['params']['context'])) {
                $urlContext['context'] = $parsedRoute['params']['context'];
            }
        }
        return [
            'id' => 'deliverySelection',
            'url' => _url('index', 'DeliverySelection', 'taoProctoring', $urlContext),
            'label' => __('Deliveries'),
        ];
    }
}

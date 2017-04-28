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
 * Provides breadcrumbs for the Reporting controller.
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
class ReportingService extends ConfigurableService implements Breadcrumbs
{
    const SERVICE_ID = 'taoProctoring/Reporting/breadcrumbs';
    
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
     * Gets the breadcrumbs for the sessionHistory page
     * @param string $route
     * @param array $parsedRoute
     * @return array
     */
    protected function breadcrumbsIndex($route, $parsedRoute) {
        $urlContext = [];
        if (isset($parsedRoute['params'])) {
            if (isset($parsedRoute['params']['session'])) {
                $session = $parsedRoute['params']['session'];
                $urlContext['session'] = is_array($session) ? implode(',', $session) : $session;
            }
            if (isset($parsedRoute['params']['delivery'])) {
                $urlContext['delivery'] = $parsedRoute['params']['delivery'];
            }
            if (isset($parsedRoute['params']['context'])) {
                $urlContext['context'] = $parsedRoute['params']['context'];
            }
        }

        $breadcrumbs = array(
            'id' => 'history',
            'url' => _url('sessionHistory', 'Reporting', 'taoProctoring', $urlContext),
            'label' => __('Session history')
        );

        return $breadcrumbs;
    }
}

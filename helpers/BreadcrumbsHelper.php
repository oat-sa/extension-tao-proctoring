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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\helpers;

use \core_kernel_classes_Resource;
use oat\oatbox\service\ServiceManager;
use oat\taoProctoring\model\textConverter\ProctoringTextConverter;

/**
 * Allow creating breakcrumbs easily
 */
class BreadcrumbsHelper
{
    /**
     * Tool to change text denomination
     *
     * @var ProctoringTextConverter
     */
    static protected $textConverterService;

    /**
     * Create breadcrumb for Delivery::index
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array $alternativeRoutes
     * @return array
     */
    public static function deliveries(core_kernel_classes_Resource $testCenter, $alternativeRoutes = array())
    {
        $breadcrumbs = array(
            'id' => 'deliveries',
            'url' => _url('index', 'Delivery', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('Sessions')
        );
        if(count($alternativeRoutes)){
            $breadcrumbs['entries'] = $alternativeRoutes;
        }
        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Delivery::monitoring
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param core_kernel_classes_Resource $delivery
     * @param array $deliveries
     * @return array
     */
    public static function deliveryMonitoring(core_kernel_classes_Resource $testCenter, core_kernel_classes_Resource $delivery, $deliveries = array())
    {
        //list also other available deliveries
        $breadcrumbs =  array(
            'id' => 'deliveryMonitoring',
            'url' => _url('monitoring', 'Delivery', null, array('testCenter' => $testCenter->getUri(), 'delivery' => $delivery->getUri())),
            'label' => $delivery->getLabel()
        );

        $otherDeliveries = array_filter($deliveries, function($value) use ($delivery) {
            return $value['id'] != $delivery->getUri();
        });

        if (count($otherDeliveries)) {
            $breadcrumbs['entries'] = $otherDeliveries;
        }

        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Delivery::monitoring
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array $deliveries
     * @return array
     */
    public static function deliveryMonitoringAll(core_kernel_classes_Resource $testCenter, $deliveries = array())
    {
        //list also other available deliveries
        $breadcrumbs =  array(
            'id' => 'deliveryMonitoring',
            'url' => _url('monitoringAll', 'Delivery', null, array('testCenter' => $testCenter->getUri())),
            'label' =>  __('All Sessions')
        );

        $otherDeliveries = array_filter($deliveries, function($value) {
            return $value['id'] != 'all';
        });

        if (count($otherDeliveries)) {
            $breadcrumbs['entries'] = $otherDeliveries;
        }

        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Delivery::manage
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param core_kernel_classes_Resource $delivery
     * @param string $page
     * @return array
     */
    public static function manageTestTakers(core_kernel_classes_Resource $testCenter, core_kernel_classes_Resource $delivery, $page)
    {
        $entries = array(
            array(
                'id' => 'manage',
                'url' => _url('manage', 'Delivery', null, array('testCenter' => $testCenter->getUri(), 'delivery' => $delivery->getUri())),
                'label' => __('Manage Test Takers')
            ),
            array(
                'id' => 'testTakers',
                'url' => _url('testTakers', 'Delivery', null, array('testCenter' => $testCenter->getUri(), 'delivery' => $delivery->getUri())),
                'label' => __('Add Test Takers')
            )
        );

        $currentPage = array_filter($entries, function($value) use($page) {
            return $value['id'] == $page;

        });

        $otherPages = array_filter($entries, function($value) use($page) {
            return $value['id'] != $page;

        });

        $breadcrumbs = current($currentPage);
        $breadcrumbs['entries'] = $otherPages;

        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Diagnostic::index
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array $alternativeRoutes
     * @return array
     */
    public static function diagnostics(core_kernel_classes_Resource $testCenter, $alternativeRoutes = array())
    {
        $breadcrumbs = array(
            'id' => 'diagnostics',
            'url' => _url('index', 'Diagnostic', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('Readiness check')
        );
        if(count($alternativeRoutes)){
            $breadcrumbs['entries'] = $alternativeRoutes;
        }
        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Diagnostic::deliveriesByProctor
     *
     * @param core_kernel_classes_Resource $testCenter
     * @return array
     */
    public static function deliveriesByProctor(core_kernel_classes_Resource $testCenter)
    {
        $breadcrumbs = array(
            'id' => 'deliveries',
            'url' => _url('deliveriesByProctor', 'Diagnostic', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('Deliveries')
        );

        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Reporting::index
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array $alternativeRoutes
     * @return array
     */
    public static function reporting(core_kernel_classes_Resource $testCenter, $alternativeRoutes = array())
    {
        $breadcrumbs = array(
            'id' => 'reporting',
            'url' => _url('index', 'Reporting', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('Assessment Activity Reporting')
        );
        if(count($alternativeRoutes)){
            $breadcrumbs['entries'] = $alternativeRoutes;
        }
        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for Reporting::sessionHistory
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param core_kernel_classes_Resource $delivery
     * @param array $sessions
     * @return array
     */
    public static function sessionHistory(core_kernel_classes_Resource $testCenter, $delivery, $sessions)
    {
        $urlContext = [
            'testCenter' => $testCenter->getUri(),
            'sessions' => is_array($sessions) ? implode(',', $sessions) : $sessions
        ];
        if ($delivery) {
            $urlContext['delivery'] = $delivery->getUri();
        }

        $breadcrumbs = array(
            'id' => 'history',
            'url' => _url('sessionHistory', 'Reporting', null, $urlContext),
            'label' => __('Session history')
        );

        return $breadcrumbs;
    }

    /**
     * Create breadcrumb for ProctorManager::index
     * @return array
     */
    public static function proctorManager()
    {
        return array(
            'id' => 'proctorManager',
            'url' => _url('index', 'ProctorManager'),
            'label' => self::convert('Manage Proctors')
        );
    }

    /**
     * Convert a text $key by TextConverterService content
     *
     * @param $key
     * @return mixed
     */
    static protected function convert($key)
    {
        if (! self::$textConverterService) {
            self::$textConverterService = ServiceManager::getServiceManager()->get(ProctoringTextConverter::SERVICE_ID);
        }
        return self::$textConverterService->get($key);
    }
}
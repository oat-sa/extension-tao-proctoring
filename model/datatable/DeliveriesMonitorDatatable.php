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

namespace oat\taoProctoring\model\datatable;

use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\tao\model\datatable\DatatablePayload;
use oat\oatbox\service\ServiceManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\helpers\DeliveryHelper;

/**
 * Class DeliveriesMonitorDatatable
 * @package oat\taoProctoring\model\datatable
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class DeliveriesMonitorDatatable implements DatatablePayload, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var DatatableRequest
     */
    protected $request;

    /**
     * @var \core_kernel_classes_Resource
     */
    protected $delivery;

    /**
     * DeliveriesMonitorDatatable constructor.
     * @param \core_kernel_classes_Resource $delivery
     */
    public function __construct(\core_kernel_classes_Resource $delivery)
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $request = DatatableRequest::fromGlobals();
        $this->request = $request;
        $this->delivery = $delivery;
    }

    public function getPayload()
    {
        $context = $this->getRequestData()->hasParameter('context') ? $this->getRequestData()->getParameter('context') : null;
        $filters = $this->request->getFilters();
        $options = [];
        if (isset($filters['start_time'])) {
            $times = explode(' - ', $filters['start_time']);
            $from = \DateTime::createFromFormat('Y/m/d', $times[0]);
            $from->setTime(0, 0, 0);
            $filters[] = ['start_time' => '>' . $from->getTimestamp()];
            if (isset($times[1])) {
                $to = \DateTime::createFromFormat('Y/m/d', $times[1]);
                $to->setTime(23, 59, 59);
                $filters[] = ['start_time' => '<' . $to->getTimestamp()];
            }
            unset($filters['start_time']);
        }
        $options['filters'] = $filters;
        $orderCol = DeliveryHelper::adjustColumnName($this->request->getSortBy());
        if ($orderCol) {
            $options['order'] = $orderCol . ' ' . $this->request->getSortOrder();
        }
        $options['limit'] = $this->request->getRows();
        $options['offset'] = ($this->request->getPage() - 1) * $this->request->getRows();

        $service = $this->getServiceLocator()->get(ProctorService::SERVICE_ID);
        $proctor = \common_session_SessionManager::getSession()->getUser();
        $executions = $service->getProctorableDeliveryExecutions($proctor, $this->delivery, $context, $options);
        $total = $service->getProctorableDeliveryExecutions($proctor, $this->delivery, $context, array_merge(
            $options,
            [
                'limit' => null,
                'offset' => 0,
            ]
        ));
        $result = $this->doPostProcessing($executions, count($total));

        return $result;
    }

    /**
     * @param array $executionsData
     * @param integer $total
     * @return array
     */
    protected function doPostProcessing(array $executionsData, $total)
    {
        return [
            'success' => true,
            'total' => $total,
            'page' => $this->request->getPage(),
            'rows' => $this->request->getRows(),
            'data' => DeliveryHelper::buildDeliveryExecutionData($executionsData),
        ];
    }

    /**
     * @return \Request
     */
    protected function getRequestData()
    {
        return \Context::getInstance()->getRequest();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getPayload();
    }
}
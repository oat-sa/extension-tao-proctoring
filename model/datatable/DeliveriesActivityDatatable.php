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
use oat\taoProctoring\model\ActivityMonitoringService;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class DeliveriesActivityDatatable
 * @package oat\taoProctoring\model\datatable
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class DeliveriesActivityDatatable implements DatatablePayload, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var DatatableRequest
     */
    protected $request;

    /**
     * DeliveriesActivityDatatable constructor.
     */
    public function __construct()
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $this->request = DatatableRequest::fromGlobals();
    }

    public function getPayload()
    {
        $service = $this->getServiceLocator()->get(ActivityMonitoringService::SERVICE_ID);
        $data = $service->getData()['deliveries_statistics'];

        $this->doSorting($data);
        $result = $this->doPostProcessing($data);

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function doPostProcessing(array $result)
    {
        $payload = [
            'data' => $result,
            'page' => (integer) $this->request->getPage(),
            'records' => (integer) count($result),
            'total' => (integer) count($result)
        ];
        return $payload;
    }

    /**
     * @param array $result
     */
    protected function doSorting(array &$result)
    {
        $sortBy = $this->request->getSortBy();
        $sortOrder = strcasecmp($this->request->getSortOrder(), 'asc') === 0 ? SORT_ASC : SORT_DESC;
        $flag = ($sortBy === 'label') ? SORT_STRING | SORT_FLAG_CASE : SORT_NUMERIC;
        array_multisort(array_column($result, $sortBy), $sortOrder, $flag, $result);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getPayload();
    }
}
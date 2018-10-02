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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoProctoring\model\Tasks;

use oat\oatbox\extension\AbstractAction;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use common_report_Report as Report;

/**
 * Class DeliveryUpdaterTask
 * @package oat\taoProctoring\model\Tasks
 */
class DeliveryUpdaterTask extends AbstractAction implements \JsonSerializable
{
    /**
     * @param array $params
     * @return Report
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params)
    {
        if (count($params) < 2) {
            throw new \common_exception_MissingParameter();
        }
        $resourceUri = array_shift($params);
        $metadataValue = array_shift($params);

        $report = Report::createSuccess();
        $this->updateDeliveryLabels($resourceUri, $metadataValue);
        $report->add(Report::createSuccess(__('Resource update task is completed')));
        return $report;
    }

    /**
     * @param string $resourceUri
     * @param string $metadataValue
     * @return bool
     */
    public function updateDeliveryLabels($resourceUri, $metadataValue)
    {
        /** @var DeliveryMonitoringService $service */
        $service = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        $deliveryExecutionsData = $service->find([
            DeliveryMonitoringService::DELIVERY_ID => $resourceUri,
        ], []);

        foreach ($deliveryExecutionsData as $data) {
            $data->update(DeliveryMonitoringService::DELIVERY_NAME, $metadataValue);
            $success = $service->partialSave($data);
            if (!$success) {
                \common_Logger::w('Monitor cache for delivery ' . $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID] . ' could not be updated. Label has not been changed');
            }
        }
        return true;
    }
    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }
}

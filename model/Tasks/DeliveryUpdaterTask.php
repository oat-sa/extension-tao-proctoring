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
use oat\oatbox\service\ServiceManager;
use oat\tao\model\event\MetadataModified;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoProctoring\model\monitorCache\update\DeliveryUpdater;
use common_report_Report as Report;

/**
 * Class DeliveryUpdaterTask
 * @package oat\taoProctoring\model\Tasks
 */
class DeliveryUpdaterTask extends AbstractAction implements \JsonSerializable
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $report = Report::createSuccess();
        $update = new DeliveryUpdater();
        $service = $this->getServiceLocator()->get($params['service']);
        $update->changeLabel($service, $params['resourceUri'], $params['metadataValue']);
        $report->add(Report::createSuccess(__('Resource update task is completed')));
        return $report;
    }

    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @param $service
     * @param \core_kernel_classes_Resource $resource
     * @param $metadataValue
     * @return TaskInterface
     */
    public static function createTask($service, \core_kernel_classes_Resource $resource, $metadataValue)
    {
        $action = new self();
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = ServiceManager::getServiceManager()->get(QueueDispatcher::SERVICE_ID);

        $parameters = [
            'service' => $service,
            'resourceUri' => $resource->getUri(),
            'metadataValue' => $metadataValue
        ];

        return $queueDispatcher->createTask($action, $parameters, __('Updating resource "%s"', $resource->getLabel()), null, true);
    }
}

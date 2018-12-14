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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\service;

use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\export\implementation\CsvExporter;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\tao\model\taskQueue\Task\FilesystemAwareTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;

abstract class AbstractIrregularityReport extends ConfigurableService implements Action
{
    use OntologyAwareTrait;
    use FilesystemAwareTrait;

    const SERVICE_ID = 'taoProctoring/irregularity';

    /**
     * return formated string for export file name
     *
     * @param $time
     * @return false|string
     */
    protected function getFormatedDateForFileName($time)
    {
        return date('Y-m-d.H.i.s', $time);
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param string                        $from
     * @param string                        $to
     * @return TaskInterface
     */
    public function getIrregularities(\core_kernel_classes_Resource $delivery, $from = '', $to = '')
    {
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcher::SERVICE_ID);

        $action = $this->getServiceLocator()->get(self::SERVICE_ID);
        $parameters = [
            'deliveryId' => $delivery->getUri(),
            'from'       => $from,
            'to'         => $to,
        ];

        return $queueDispatcher->createTask(
            $action,
            $parameters,
            __(
                'CSV irregularities export for delivery "%s" from %s to %s',
                $delivery->getLabel(),
                $this->getFormatedDateForFileName($from),
                $this->getFormatedDateForFileName($to)
            )
        );
    }

    public function __invoke($params)
    {
        $this->getServiceLocator()
            ->get(\common_ext_ExtensionsManager::SERVICE_ID)
            ->getExtensionById('taoResultServer');

        if (!isset($params['deliveryId'])) {
            throw new \InvalidArgumentException('Delivery uri is missing for irregularity export');
        }

        if (!isset($params['from'])) {
            throw new \InvalidArgumentException('From date is missing for irregularity export');
        }

        if (!isset($params['to'])) {
            throw new \InvalidArgumentException('To date is missing for irregularity export');
        }

        $delivery = $this->getResource($params['deliveryId']);
        $data = $this->getIrregularitiesTable($delivery, $params['from'], $params['to']);
        $exporter = new CsvExporter($data);

        $filePrefix = $this->saveStringToStorage($exporter->export(), $this->getFileName($delivery, $params));

        return $filePrefix === false
            ? Report::createFailure(__('Unable to create irregularities export for %s', $delivery->getLabel()))
            : Report::createSuccess(__('Irregularities for "%s" successfully exported', $delivery->getLabel()), $filePrefix);
    }

    protected function getFileName(\core_kernel_classes_Resource $delivery, array $params)
    {
        return strtolower(
            'irregularities_'
            .\tao_helpers_File::getSafeFileName($delivery->getLabel()).'_'
            .$this->getFormatedDateForFileName($params['from']).'_'
            .$this->getFormatedDateForFileName($params['to']).'_'
            .date('YmdHis') . rand(10, 99) //more unique name
            .'.csv'
        );
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param string                        $from
     * @param string                        $to
     * @return array
     */
    abstract public function getIrregularitiesTable(\core_kernel_classes_Resource $delivery, $from = '', $to = '');

    /**
     * @see FilesystemAwareTrait::getFileSystemService()
     */
    protected function getFileSystemService()
    {
        return $this->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID);
    }
}
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


use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\tao\model\export\implementation\CsvExporter;

abstract class AbstractIrregularityReport extends ConfigurableService implements Action
{

    use OntologyAwareTrait;

    const SERVICE_ID = 'taoProctoring/irregularity';

    /**
     * return formated string for export file name
     * @param $time
     * @return false|string
     */
    protected function getFormatedDateForFileName($time) {
        return date('Y-m-d.H.i.s' , $time);
    }

    public function getIrregularities(\core_kernel_classes_Resource $delivery, $from = '', $to = ''){

        /**
         * @var $taskQueue Queue
         */
        $taskQueue = $this->getServiceManager()->get(Queue::SERVICE_ID);
        $action = self::SERVICE_ID ;
        $parameters = [
            'deliveryId' => $delivery->getUri(),
            'from'       => $from,
            'to'         => $to
        ];
        $label = $delivery->getLabel() . ' from ' . $this->getFormatedDateForFileName($from ). ' to ' . $this->getFormatedDateForFileName( $to );
        $taskName  = 'export/irregularity';
        $task = $taskQueue->createTask($action , $parameters , false , $label , $taskName);
        return $task;
    }


    public function __invoke($params) {
        /**
         * @var $extload \common_ext_ExtensionsManager
         */
        $extload = $this->getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);
        $extload->getExtensionById('taoResultServer');

        $deliveryId = $params['deliveryId'];
        $from       = $params['from'];
        $to         = $params['to'];

        $delivery = new \core_kernel_classes_Resource($deliveryId);
        $data = $this->getIrregularitiesTable( $delivery ,  $from , $to );
        $exporter = new CsvExporter($data);
        $csv      = $exporter->export();
        /**
         * @var FileSystemService $fileSystemService
         */
        $fileSystemService = ServiceManager::getServiceManager()->get(FileSystemService::SERVICE_ID);
        $fileSystem        = $fileSystemService->getFileSystem(Queue::FILE_SYSTEM_ID);
        $fileName          = 'irregularities/' . \tao_helpers_File::getSafeFileName($delivery->getLabel() . ' ' . $this->getFormatedDateForFileName($from) . ' ' . $this->getFormatedDateForFileName($to) . '.csv' );
        $return            = $fileSystem->put($fileName , $csv);
        if($return === false) {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR , __('unable to create irregularities export for %s' , $delivery->getLabel()));
        } else {
            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS , __('successfully create export for %s' , $delivery->getLabel()) , $fileName);
            $report->add(new \common_report_Report(\common_report_Report::TYPE_INFO , $return));
        }

        return $report;

    }


    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param string $from
     * @param string $to
     * @return array
     */
    abstract public function getIrregularitiesTable(\core_kernel_classes_Resource $delivery, $from = '', $to = '');

}
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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;

use oat\oatbox\service\ServiceManager;
use oat\tao\model\export\implementation\CsvExporter;
use oat\taoOutcomeUi\model\ResultsService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\controller\form\IrregularitiesExportForm;
use oat\taoProctoring\model\service\IrregularityReport;

/**
 * Irregularity controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Irregularity extends \tao_actions_QueueAction
{

    protected function setQueueData() {
        $this->setData('queueId', 'export/irregularity');
        $this->setView('Irregularities/index.tpl');
    }



    /**
     * Displays the form to export irregularities
     */
    public function index()
    {

        $formContainer = new IrregularitiesExportForm($this->getRequestParameter('uri'));
        $myForm = $formContainer->getForm();

        $asyncQueue = $this->isAsyncQueue();

        if ($myForm->isValid() && $myForm->isSubmited()) {
            $delivery = new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($this->getRequestParameter('uri')));
            $from = ($this->hasRequestParameter('from')) ? strtotime($this->getRequestParameter('from')) : '';
            $to = ($this->hasRequestParameter('to')) ? strtotime($this->getRequestParameter('to')) : '';

            $task = $this->getIrregularities($delivery, $from, $to);
            $report = $this->getTaskReport($task);

            if (!$asyncQueue) {
                $filename = $this->getReportAttachment($report);
                if ($filename) {
                    $file = $this->getFile($filename);
                    if ($file !== false) {

                        $this->prepareDownload($filename, 'text/csv');
                        \tao_helpers_Http::returnStream(new \GuzzleHttp\Psr7\Stream($file));
                        return;
                    }
                }
            }

            $this->returnReport($report);

        } else {

            $this->setData('asyncQueue', $asyncQueue);
            $this->setData('myForm', $myForm->render());
            $this->setQueueData();
        }

    }

    /**
     * @param $delivery
     * @param string $from
     * @param string $to
     * @return array
     */
    private function getIrregularities($delivery, $from = '', $to = ''){
        /**
         * @var $IrregularityReport IrregularityReport
         */
        $IrregularityReport = $this->getServiceManager()->get(IrregularityReport::SERVICE_ID);

        return $IrregularityReport->getIrregularities($delivery, $from , $to );

    }
}

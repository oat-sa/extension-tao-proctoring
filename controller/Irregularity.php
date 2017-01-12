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

/**
 * Irregularity controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Irregularity extends \tao_actions_CommonModule
{
    /**
     * Displays the form to export irregularities
     */
    public function index()
    {

        $formContainer = new IrregularitiesExportForm($this->getRequestParameter('uri'));
        $myForm = $formContainer->getForm();


        $this->setData('myForm', $myForm->render());
        $this->setData('formTitle', __('Export Irregularities'));

        $this->setView('form.tpl', 'tao');
    }

    public function exportIrregularities()
    {
        if (!$this->hasRequestParameter('uri')) {
            $response = array(
                'success' => false,
                'message' => __('You must select a delivery in order to export its irregularities')
            );
            $this->returnJson($response, 200);

            return;
        }
        $delivery = new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($this->getRequestParameter('uri')));
        $from = ($this->hasRequestParameter('from')) ? strtotime($this->getRequestParameter('from')) : '';
        $to = ($this->hasRequestParameter('to')) ? strtotime($this->getRequestParameter('to')) : '';

        try {
            $export = $this->getIrregularities($delivery, $from, $to);
        } catch (\common_Exception $e) {
            $response = array('success' => false, 'message' => __('Something went wrong during the export'));
            $this->returnJson($response, 200);

            return;
        }
        setcookie('fileDownload', 'true', 0, '/');
        $exporter = new CsvExporter($export);
        $exporter->export(false, true);
    }


    private function getIrregularities($delivery, $from = '', $to = ''){
        $export = array(
            array(__('date'), __('author'), __('test taker'), __('category'), __('subcategory'), __('comment'))
        );

        $deliveryLog = ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
        $service = ResultsService::singleton();
        $implementation = $service->getReadableImplementation($delivery);

        $service->setImplementation($implementation);


        $results = $service->getImplementation()->getResultByDelivery(array($delivery->getUri()));

        foreach($results as $res){
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($res['deliveryResultIdentifier']);
            $logs = $deliveryLog->get(
                $deliveryExecution->getIdentifier(),
                'TEST_IRREGULARITY'
            );
            foreach($logs as $data){
                $exportable = array();
                if((empty($from) || $data['created_at'] > $from) && (empty($to) || $data['created_at'] < $to)){

                    $testTaker = new \core_kernel_classes_Resource($res['testTakerIdentifier']);
                    $author = new \core_kernel_classes_Resource($data['created_by']);
                    $exportable[] = \tao_helpers_Date::displayeDate($data['created_at']);
                    $exportable[] = $author->getLabel();
                    $exportable[] = $testTaker->getLabel();
                    $exportable[] = $data['data']['reason']['reasons']['category'];
                    $exportable[] = (isset($data['data']['reason']['reasons']['subCategory'])) ? $data['data']['reason']['reasons']['subCategory'] : '';
                    $exportable[] = $data['data']['reason']['comment'];
                    $export[] = $exportable;
                }
            }
        }

        return $export;

    }
}

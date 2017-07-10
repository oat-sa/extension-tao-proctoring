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


use oat\oatbox\service\ServiceManager;
use oat\taoOutcomeUi\model\ResultsService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;

class IrregularityReport extends AbstractIrregularityReport
{


    public function getIrregularitiesTable(\core_kernel_classes_Resource $delivery, $from = '', $to = '')
    {
        $export = array(
            array(__('date'), __('author'), __('test taker'), __('category'), __('subcategory'), __('comment'))
        );
        \common_Logger::d(var_export($delivery , true));
        $deliveryLog = ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
        $service = ResultsService::singleton();
        $implementation = $service->getReadableImplementation($delivery);

        $service->setImplementation($implementation);


        $results = $service->getImplementation()->getResultByDelivery(array($delivery->getUri()));

        foreach ($results as $res) {
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($res['deliveryResultIdentifier']);
            $logs = $deliveryLog->get(
                $deliveryExecution->getIdentifier(),
                'TEST_IRREGULARITY'
            );
            foreach ($logs as $data) {
                $exportable = array();
                if ((empty($from) || $data['created_at'] > $from) && (empty($to) || $data['created_at'] < $to)) {

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
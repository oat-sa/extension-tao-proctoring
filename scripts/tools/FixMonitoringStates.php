<?php
/**
 * Copyright (c) 2018 Open Assessment Technologies, S.A.
 *
 */

namespace oat\taoProctoring\scripts\tools;

use oat\dtms\DateTime;
use oat\oatbox\extension\script\ScriptAction;
use \common_report_Report as Report;
use oat\taoDelivery\model\execution\implementation\KeyValueService;
use oat\taoDelivery\model\execution\KVDeliveryExecution;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\generis\model\OntologyRdfs;


/**
 * Class FixMonitoringStates
 *
 * Fixing monitoring states if by some reason we have difference between storage and delivery executions storage
 *
 * Usage example:
 * ```
 * sudo -u www-data php index.php '\oat\taoProctoring\scripts\tools\FixMonitoringStates' --from 1537833600 --to 1537920000 --deliveryMonitoringStates http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive,http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused --deliveryExecutionStates http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAwaiting,http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized
 * ```
 * @package oat\taoProctoring\scripts\tools
 */
class FixMonitoringStates extends ScriptAction
{
    /** @var Report */
    private $report;

    private $from;
    private $to;
    private $deliveryMonitoringStates;
    private $deliveryExecutionStates;
    private $wetRun;
    private $withProgress;
    private $deliveryExecutionStatesForce;

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'Fixed bad Delivery Monitoring entries';
    }

    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'from' => [
                'longPrefix' => 'from',
                'required' => false,
                'description' => 'Date for searching from',
                'defaultValue' => time()
            ],
            'to' => [
                'longPrefix' => 'to',
                'required' => false,
                'description' => 'Date for searching to',
                'defaultValue' => time()
            ],
            'deliveryMonitoringStates' => [
                'longPrefix' => 'deliveryMonitoringStates',
                'required' => true,
                'description' => 'List of states for searching'
            ],
            'deliveryExecutionStates' => [
                'longPrefix' => 'deliveryExecutionStates',
                'required' => true,
                'description' => 'List of states for filtering.'
            ],
            'deliveryExecutionStatesForce' => [
                'longPrefix' => 'deliveryExecutionStatesForce',
                'required' => false,
                'description' => 'Force state for executions',
                'defaultValue' => ''
            ],
            'withProgress' => [
                'longPrefix' => 'withProgress',
                'required' => false,
                'description' => 'Should be a result only with progress? True by default',
                'defaultValue' => 1
            ],
            'wetRun' => [
                'longPrefix' => 'wetRun',
                'required' => false,
                'description' => 'Wet run',
                'defaultValue' => 0
            ]
        ];
    }

    /**
     * @return Report
     */
    protected function run()
    {
        try {
            $this->init();
        } catch (\Exception $e) {
            return new Report(Report::TYPE_ERROR, $e->getMessage());
        }
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        $deliveryExecutionsData = $deliveryMonitoringService->find([
            [
                DeliveryMonitoringService::STATUS => $this->deliveryMonitoringStates
            ],
            'AND',
            [['start_time' => '<'.$this->to], 'AND', ['start_time' => '>'.$this->from]],
        ]);
        $deliveryExecutionService = ServiceProxy::singleton();
        $this->report->add(new Report(Report::TYPE_INFO, "Found ".sizeof($deliveryExecutionsData). " items."));
        $count = 0;

        foreach ($deliveryExecutionsData as $deliveryExecutionData) {

            $data = $deliveryExecutionData->get();
            $deliveryExecution = $deliveryExecutionService->getDeliveryExecution(
                $data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]
            );
            try {
                $deliveryExecution->getDelivery();
                try {
                    /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
                    $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
                    if ($this->withProgress && $deliveryExecutionStateService->isCancelable($deliveryExecution)) {
                        break;
                    }
                    $executionState = $deliveryExecution->getState()->getUri();
                    if (in_array($executionState, $this->deliveryExecutionStates) && $data['status'] != $executionState) {
                        $deliveryExecutionStatesForce = $this->deliveryExecutionStatesForce ?: $executionState;
                        if ($this->wetRun === true) {
                            if ($this->deliveryExecutionStatesForce) {
                                $deliveryExecution->setState($this->deliveryExecutionStatesForce);
                                $deliveryExecutionData->update(DeliveryMonitoringService::STATUS, $this->deliveryExecutionStatesForce);
                                $deliveryMonitoringService->save($deliveryExecutionData);
                                $this->report->add(new Report(Report::TYPE_INFO, "{$deliveryExecution->getIdentifier()} was updated from {$data['status']} to {$deliveryExecutionStatesForce} ."));
                            } else {
                                $deliveryExecutionData->update(DeliveryMonitoringService::STATUS, $executionState);
                                $deliveryMonitoringService->save($deliveryExecutionData);
                                $this->report->add(new Report(Report::TYPE_INFO, "{$deliveryExecution->getIdentifier()} was updated from {$data['status']} to {$executionState} ."));
                            }
                            $count++;
                        } else {
                            if ($this->deliveryExecutionStatesForce) {
                                $this->report->add(new Report(Report::TYPE_INFO, "Will update state for {$deliveryExecution->getIdentifier()} from {$data['status']} to {$this->deliveryExecutionStatesForce} ."));
                            } else {
                                $this->report->add(new Report(Report::TYPE_INFO, "Will update state for {$deliveryExecution->getIdentifier()} from {$data['status']} to {$executionState} ."));
                            }

                            $count++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->report->add(new Report(Report::TYPE_ERROR, $e->getMessage()));
                }
            } catch (\Exception $e) {
                $this->report->add(new Report(Report::TYPE_INFO, "Execution with ID {$data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]} doesn't exist."));
                $this->report->add(new Report(Report::TYPE_INFO, "Execution with ID {$data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]} will be created in storage."));
                $this->initExecutionData($data);
            }


        }

        $this->report->add(new Report(Report::TYPE_INFO, "Was updated {$count} items."));
        return $this->report;
    }


    /**
     * Initialize parameters
     */
    private function init()
    {

        $this->from = $this->getOption('from');
        $this->to = $this->getOption('to');
        $this->deliveryExecutionStatesForce = $this->getOption('deliveryExecutionStatesForce');
        $this->deliveryMonitoringStates = explode(',', $this->getOption('deliveryMonitoringStates')?:'');
        $this->deliveryExecutionStates = explode(',', $this->getOption('deliveryExecutionStates')?:'');
        $this->wetRun = (boolean) $this->getOption('wetRun');
        $this->withProgress = (boolean) $this->getOption('withProgress');
        $this->report = new Report(
            Report::TYPE_INFO,
            'Starting checking delivery monitoring entries');
    }

    protected function initExecutionData($data)
    {
        $dateObj = DateTime::createFromFormat('U.u', $data['start_time']);
        $startTime = $dateObj->format('0.u00 U');
        $executionData = array(
            OntologyRdfs::RDFS_LABEL => $data['delivery_name'],
            OntologyDeliveryExecution::PROPERTY_DELIVERY  => $data['delivery_id'],
            OntologyDeliveryExecution::PROPERTY_SUBJECT => $data['test_taker'],
            OntologyDeliveryExecution::PROPERTY_TIME_START => $startTime,
            OntologyDeliveryExecution::PROPERTY_STATUS => DeliveryExecution::STATE_AWAITING
        );
        $executionDataJson = json_encode($executionData);

        // deliveryExecutions
        $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        $deliveryService = $extension->getConfig('execution_service');
        if ($deliveryService instanceof KeyValueService) {
            if ($this->wetRun === true) {
                $kvDe = new KVDeliveryExecution($deliveryService, $data['delivery_execution_id'], $executionData);
                $deliveryService->update($kvDe);
                $this->report->add(new Report(Report::TYPE_INFO, "Was created execution with id state for {$data['delivery_execution_id']} and body {$executionDataJson}."));
            } else {
                $this->report->add(new Report(Report::TYPE_INFO, "Will create execution with id state for {$data['delivery_execution_id']} and body {$executionDataJson}."));
            }
        }
    }
}
<?php
/**
 * Copyright (c) 2018 Open Assessment Technologies, S.A.
 *
 */

namespace oat\taoProctoring\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use \common_report_Report as Report;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

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
            if (!$deliveryExecution) {
                $this->report->add(new Report(Report::TYPE_INFO, "Execution with ID {$data[DeliveryMonitoringService::DELIVERY_EXECUTION_ID]} doesn't exist."));
            }
            try {
                /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
                $deliveryExecutionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);
                if (!$deliveryExecutionStateService->isCancelable($deliveryExecution)){
                    $executionState = $deliveryExecution->getState()->getUri();
                    if (in_array($executionState, $this->deliveryExecutionStates) && $data['status'] != $executionState) {
                        if ($this->wetRun === true) {
                            $deliveryExecutionData->update(DeliveryMonitoringService::STATUS, $executionState);
                            $deliveryMonitoringService->save($deliveryExecutionData);
                            $this->report->add(new Report(Report::TYPE_INFO, "{$deliveryExecution->getIdentifier()} was updated from {$data['status']} to {$executionState} ."));
                            $count++;
                        } else {
                            $this->report->add(new Report(Report::TYPE_INFO, "Will update state for {$deliveryExecution->getIdentifier()} from {$data['status']} to {$executionState} ."));
                            $count++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->report->add(new Report(Report::TYPE_ERROR, $e->getMessage()));
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
        $this->deliveryMonitoringStates = explode(',', $this->getOption('deliveryMonitoringStates')?:'');
        $this->deliveryExecutionStates = explode(',', $this->getOption('deliveryExecutionStates')?:'');
        $this->wetRun = (boolean) $this->getOption('wetRun');
        $this->report = new Report(
            Report::TYPE_INFO,
            'Starting checking delivery monitoring entries');
    }
}

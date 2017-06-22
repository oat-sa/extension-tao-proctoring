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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts;

use oat\oatbox\action\Action;
use oat\taoProctoring\model\ProctorService;
use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;

/**
 * Class UpdateOldDeliveriesProctoringStatus
 *
 * @package oat\taoProctoring\scripts
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 * Run example: `sudo php index.php 'oat\taoProctoring\scripts\UpdateOldDeliveriesProctoringStatus' $uri $uri --dryrun`
 */
class UpdateOldDeliveriesProctoringStatus implements Action
{
    use OntologyAwareTrait;

    /**
     * @var \Report
     */
    protected $report;

    /**
     * @param $params
     * @return Report|\Report
     */
    public function __invoke($params)
    {
        $dryrun = in_array('dryrun', $params) || in_array('--dryrun', $params);

        $this->report = new Report(
            Report::TYPE_INFO,
            'Cancellation of updating deliveries...'
        );

        if ($dryrun) {
            $this->addReport(Report::TYPE_INFO, "Note: script ran in 'dryrun' mode. Data will not be updated.");
        }

        $deliveries = $this->getDeliveries($params);

        $accessibleProperty = $this->getProperty(ProctorService::ACCESSIBLE_PROCTOR);

        $count = 0;
        /** @var \core_kernel_classes_Resource $delivery */
        foreach ($deliveries as $delivery) {
            if ($delivery->exists()) {
                try {
                    if(!$dryrun) {
                        $delivery->setPropertyValue($accessibleProperty, ProctorService::ACCESSIBLE_PROCTOR_ENABLED);
                    } else {
                        $this->addReport(Report::TYPE_INFO, "Update ".$delivery->getUri());
                    }

                    $count++;
                } catch (\Exception $e) {
                    $this->addReport(Report::TYPE_ERROR, $e->getMessage());
                }

            }
        }

        $msg = $count > 0 ? "{$count} deliveries has been updated." : "Deliveries not found.";
        $this->addReport(Report::TYPE_INFO, $msg);

        return $this->report;
    }

    /**
     * @param array $params
     * @return array
     */
    protected function getDeliveries($params = [])
    {
        if ($params) {
            $deliveries = array();
            foreach ($params as $uri) {
                if (\common_Utils::isUri($uri)){
                    $delivery = $this->getResource($uri);
                    $deliveries[] = $delivery;
                }
            }
        } else {
            $deliveryClass = $this->getClass('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDelivery');
            $deliveries = $deliveryClass->getInstances(true);
        }

        return $deliveries;
    }

    /**
     * @param $type
     * @param string $message
     */
    protected function addReport($type, $message)
    {
        $this->report->add(new Report(
            $type,
            $message
        ));
    }
}

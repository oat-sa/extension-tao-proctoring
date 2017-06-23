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
 *
 */

namespace oat\taoProctoring\scripts\tools;

use oat\oatbox\action\Action;
use oat\taoProctoring\model\ProctorService;
use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;

/**
 * Class UpdateProctorableStatus
 *
 * @package oat\taoProctoring\scripts\tools
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 * Run example: `sudo php index.php 'oat\taoProctoring\scripts\tools\UpdateProctorableStatus' $uri $uri --prod --mode=off`
 * $uri - Uri of delivery
 * --prod - Start script in production mode
 * --mode - (off|on) change proctorable status to on or off state
 */
class UpdateProctorableStatus implements Action
{
    use OntologyAwareTrait;

    /**
     * @var bool
     */
    private $dryrun = true;

    /**
     * @var bool
     */
    private $mode = true;

    /**
     * @var array
     */
    private $uriArray = [];

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
        $this->verifyParams($params);

        if ($this->dryrun) {
            $this->addReport(Report::TYPE_WARNING, "Note: script ran in 'dryrun' mode. Data will not be updated.");
        }

        $this->addReport(
            Report::TYPE_INFO,
            'Starting of updating deliveries...'
        );

        $deliveries = $this->getDeliveries();

        $accessibleProperty = $this->getProperty(ProctorService::ACCESSIBLE_PROCTOR);

        $count = 0;
        /** @var \core_kernel_classes_Resource $delivery */
        foreach ($deliveries as $delivery) {
            if ($delivery->exists()) {
                try {
                    if(!$this->dryrun) {
                        if ($this->mode) {
                            $delivery->editPropertyValues($accessibleProperty, ProctorService::ACCESSIBLE_PROCTOR_ENABLED);
                        } else {
                            $delivery->removePropertyValue($accessibleProperty, ProctorService::ACCESSIBLE_PROCTOR_ENABLED);
                        }
                    }
                    $this->addReport(Report::TYPE_INFO, "Updated ".$delivery->getUri());

                    $count++;
                } catch (\Exception $e) {
                    $this->addReport(Report::TYPE_ERROR, $e->getMessage());
                }

            }
        }

        $msg = $count > 0 ? "{$count} deliveries has been updated." : "Deliveries not found.";
        $this->addReport(Report::TYPE_SUCCESS, $msg);

        return $this->report;
    }

    /**
     * @return array
     */
    protected function getDeliveries()
    {
        $deliveries = [];
        if ($this->uriArray) {
            foreach ($this->uriArray as $uri) {
                $delivery = $this->getResource($uri);
                $deliveries[] = $delivery;
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
        if (!$this->report instanceof Report) {
            $this->report = new Report(
                $type,
                $message
            );
        } else {
            $this->report->add(new Report(
                $type,
                $message
            ));
        }

    }

    /**
     * @param $params
     */
    protected function verifyParams($params)
    {
        if ($params && is_array($params)) {
            foreach ($params as $uri) {
                if (\common_Utils::isUri($uri)){
                    $uriArray[] = $uri;
                }
            }
            $this->dryrun = (in_array('prod', $params) || in_array('--prod', $params)) ? false : true;
            $this->mode = (in_array('--mode=off', $params) || in_array('mode=off', $params)) ? false : true;
        }
    }
}

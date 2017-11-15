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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;
use oat\taoProctoring\helpers\DeliveryHelper;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Monitoring Proctor Administrator controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class MonitorProctorAdministrator extends Monitor
{
    const ERROR_REACTIVATE_EXECUTIONS = 6;

    /**
     * @throws \common_Exception
     */
    public function reactivateExecutions()
    {
        $deliveryExecution = $this->getRequestParameter('execution');
        $reason = $this->getRequestParameter('reason');

        if (!is_array($deliveryExecution)) {
            $deliveryExecution = array($deliveryExecution);
        }

        try {
            $data = DeliveryHelper::reactivateExecution($deliveryExecution, $reason);

            $response = [
                'success' => !count($data['unprocessed']),
                'data' => $data
            ];

            if (!$response['success']) {
                $response['errorCode'] = self::ERROR_REACTIVATE_EXECUTIONS;
                $response['errorMsg'] = __('Some delivery executions have not been reactivated');
            }

            $this->returnJson($response);
        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No delivery service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }
}
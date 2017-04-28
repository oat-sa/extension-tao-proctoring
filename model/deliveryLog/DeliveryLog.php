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
 */

namespace oat\taoProctoring\model\deliveryLog;

/**
 * Interface DeliveryLog
 *
 * @package oat\taoProctoring\model\deliveryLog
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
interface DeliveryLog
{
    const SERVICE_ID = 'taoProctoring/DeliveryLog';

    const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const EVENT_ID = 'event_id';
    const DATA = 'data';
    const CREATED_AT = 'created_at';
    const CREATED_BY = 'created_by';

    /**
     * Log data
     *
     * @param string $deliveryExecutionId
     * @param string $eventId
     * @param mixed $data
     * @param string $user user id to be stored as `created_by` value
     * @return boolean
     */
    public function log($deliveryExecutionId, $eventId, $data, $user = null);

    /**
     * Get logged data by delivery execution id
     *
     * @param string $deliveryExecutionId
     * @param sting|null $eventId - filter data by event id
     * @return mixed
     */
    public function get($deliveryExecutionId, $eventId = null);

    /**
     *
     * @return bool true if it correctly flush false otherwise
     */
    public function flush();
}
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
 */

namespace oat\taoProctoring\model;

/**
 * Interface of service to retrieve test session history
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package oat\taoProctoring
 */
interface TestSessionHistoryService
{
    const SERVICE_ID = 'taoProctoring/TestSessionHistoryService';

    const PROCTOR_ROLES = 'proctorRoles';

    /**
     * @param array $sessions List of session ids
     * @param array $options The following option is handled:
     * - periodStart:
     * - periodEnd:
     * - detailed: whether to retrieve detailed or brief report. Defaults to false (brief).
     * - sortBy: column name
     * - sortOrder: order direction (asc|desc)
     * @return array
     */
    public function getSessionsHistory(array $sessions, $options);

    /**
     * Gets the url that leads to the page listing the history
     * @param $delivery
     * @return string
     */
    public function getHistoryUrl($delivery = null);
    
    /**
     * Gets the back url that returns to the page listing the sessions
     * @param $delivery
     * @return string
     */
    public function getBackUrl($delivery = null);
}

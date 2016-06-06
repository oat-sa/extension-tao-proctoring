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
 * Interface of service to check whether test session is online.
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package oat\taoProctoring
 */
interface TestSessionConnectivityStatusService
{

    const SERVICE_ID = 'taoProctoring/TestSessionConnectivityStatusService';

    /**
     * Whether user is online
     * @param string $sessionId session
     * @return bool
     */
    public function isOnline($sessionId);

    /**
     * @param $sessionId
     * @return null|timestamp
     */
    public function getLastOnline($sessionId);
}
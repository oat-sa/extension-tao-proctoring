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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoProctoring\model\execution\DeliveryExecution as ProctoredDeliveryExecution;

/**
 * Class RegisterProctorAttemptService
 * @package oat\taoProctoring\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterProctorAttemptService extends InstallAction
{
    /**
     * @param $params
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $this->getServiceManager()
            ->get(AttemptServiceInterface::SERVICE_ID)
            ->setStatesToExclude([
                ProctoredDeliveryExecution::STATE_CANCELED
            ]);
    }
}

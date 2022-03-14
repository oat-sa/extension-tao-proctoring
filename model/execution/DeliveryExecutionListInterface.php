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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoProctoring\model\execution;

use common_exception_Error;
use common_ext_ExtensionException;

interface DeliveryExecutionListInterface
{
    public const SERVICE_ID = 'taoProctoring/DeliveryExecutionList';

    /**
     * Adjusts a list of delivery executions: add information, format the result
     * @param DeliveryExecution[] $deliveryExecutions
     * @return array
     * @throws common_ext_ExtensionException
     * @throws common_exception_Error
     * @internal param array $options
     */
    public function adjustDeliveryExecutions($deliveryExecutions);
}

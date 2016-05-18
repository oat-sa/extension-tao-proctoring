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

namespace oat\taoProctoring\model\execution;

use oat\taoDelivery\models\classes\execution\DeliveryExecution as BaseDeliveryExecution;

/**
 * Proctoring for delivery executions
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package taoProctoring
 */
class DeliveryExecution extends BaseDeliveryExecution
{
    const STATE_INIT = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusInit';

    const STATE_AWAITING = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAwaiting';

    const STATE_AUTHORIZED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusAuthorized';

    //const STATE_INPROGRESS = 'INPROGRESS';
    const STATE_ACTIVE = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusActive';

    //const STATE_COMPLETED = 'COMPLETED';
    const STATE_FINISHED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusFinished';

    const STATE_PAUSED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusPaused';
    //const STATE_PAUSED = 'PAUSED';

    const STATE_TERMINATED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionStatusTerminated';
}
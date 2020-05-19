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
 */

namespace oat\taoProctoring\model;

use common_Exception;
use common_exception_Error as Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoProctoring\model\execution\DeliveryExecutionsUpdater;
use common_report_Report as Report;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use qtism\runtime\storage\common\StorageException;
use qtism\runtime\tests\AssessmentTestSessionException;

class TerminateDeliveryExecutionsService extends DeliveryExecutionsUpdater
{
    public const SERVICE_ID = 'taoProctoring/TerminateDeliveryExecutions';

    /**
     * Terminate delivery execution
     *
     * @param      $deliveryExecution
     * @param      $executionId
     * @param bool $isEndDate
     *
     * @return mixed|void
     * @throws Error
     * @throws common_Exception
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws StorageException
     * @throws AssessmentTestSessionException
     */
    protected function action($deliveryExecution, $executionId, $isEndDate = false)
    {
        $this->getDeliveryStateService()->terminateExecution($deliveryExecution, [
            'reasons' => [
                ReasonCategoryServiceInterface::PROPERTY_CATEGORY => 'Technical',
                ReasonCategoryServiceInterface::PROPERTY_SUBCATEGORY => 'Abandoned'
            ],
            'comment' => $isEndDate
                ? 'The assessment was automatically terminated because of end time expired.'
                : 'The assessment was automatically terminated.'
        ]);

        $this->report->add(Report::createSuccess(sprintf('Execution %s successfully terminated', $executionId)));
    }
}

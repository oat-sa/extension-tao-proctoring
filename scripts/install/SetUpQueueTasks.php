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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoProctoring\model\service\AbstractIrregularityReport;

/**
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class SetUpQueueTasks extends InstallAction
{
    public function __invoke($params)
    {
        /** @var TaskLogInterface|ConfigurableService $taskLogService */
        $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);

        $taskLogService->linkTaskToCategory(AbstractIrregularityReport::class, TaskLogInterface::CATEGORY_EXPORT);

        $this->registerService(TaskLogInterface::SERVICE_ID, $taskLogService);

        return \common_report_Report::createSuccess('Task(s) successfully set up.');
    }
}

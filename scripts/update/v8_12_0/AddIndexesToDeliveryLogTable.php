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
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\scripts\update\v8_12_0;


use oat\oatbox\extension\AbstractAction;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;

class AddIndexesToDeliveryLogTable extends AbstractAction
{

    public function __invoke($params)
    {
        $deliveryLogService = $this->getServiceLocator()->get(RdsDeliveryLogService::SERVICE_ID);
        $persistenceManager = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById($deliveryLogService->getOption(RdsDeliveryLogService::OPTION_PERSISTENCE));
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        $tableData = $schema->getTable(RdsDeliveryLogService::TABLE_NAME);
        $tableData->addIndex(
            [RdsDeliveryLogService::EVENT_ID],
            'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::EVENT_ID
        );
        $tableData->addIndex(
            [RdsDeliveryLogService::CREATED_AT],
            'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::CREATED_AT
        );
        $tableData->addIndex(
            [RdsDeliveryLogService::CREATED_BY],
            'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::CREATED_BY
        );
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}

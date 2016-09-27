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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\update;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\oatbox\service\ServiceManager;

class MigrateDeliveryMonitoringData extends \common_ext_action_InstallAction
{

    public function __invoke($params)
    {
        $service = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);

        $persistence = \common_persistence_Manager::getPersistence($service->getOption(DeliveryMonitoringService::OPTION_PERSISTENCE));


        // get data from kv and fill new column
        $sql = 'SELECT ' . DeliveryMonitoringService::KV_COLUMN_VALUE . ', ' . DeliveryMonitoringService::KV_COLUMN_PARENT_ID . '
                FROM ' . DeliveryMonitoringService::KV_TABLE_NAME . '
                WHERE ' . DeliveryMonitoringService::KV_COLUMN_KEY . '=?';

        foreach($persistence->query($sql, [DeliveryMonitoringService::COLUMN_DELIVERY_ID]) as $row){
            $update = "UPDATE " . DeliveryMonitoringService::TABLE_NAME . " SET " . DeliveryMonitoringService::COLUMN_DELIVERY_ID . " =:value
                        WHERE " . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '=:delivery_execution_id';
            $persistence->exec($update, [':value'=>$row[DeliveryMonitoringService::KV_COLUMN_VALUE],':delivery_execution_id'=>$row[DeliveryMonitoringService::KV_COLUMN_PARENT_ID]]);
        }

        foreach($persistence->query($sql, [DeliveryMonitoringService::COLUMN_DELIVERY_TEST_CENTER_ID]) as $row){
            $update = "UPDATE " . DeliveryMonitoringService::TABLE_NAME . " SET " . DeliveryMonitoringService::COLUMN_DELIVERY_TEST_CENTER_ID . " =:value
                        WHERE " . DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID . '=:delivery_execution_id';
            $persistence->exec($update, [':value'=>$row[DeliveryMonitoringService::KV_COLUMN_VALUE],':delivery_execution_id'=>$row[DeliveryMonitoringService::KV_COLUMN_PARENT_ID]]);
        }

        //remove data
        $delete = 'DELETE FROM ' . DeliveryMonitoringService::KV_TABLE_NAME . '
                WHERE ' . DeliveryMonitoringService::KV_COLUMN_KEY . '=?';
        $persistence->exec($delete, [DeliveryMonitoringService::COLUMN_DELIVERY_ID]);
        $persistence->exec($delete, [DeliveryMonitoringService::COLUMN_DELIVERY_TEST_CENTER_ID]);

        // use these newly created column
        $deliveryMonitoringService = $this->getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
        $deliveryMonitoringService->setOption(
            DeliveryMonitoringService::OPTION_PRIMARY_COLUMNS,
            [
                DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID,
                DeliveryMonitoringService::COLUMN_DELIVERY_ID,
                DeliveryMonitoringService::COLUMN_DELIVERY_TEST_CENTER_ID,
                DeliveryMonitoringService::COLUMN_STATUS,
                DeliveryMonitoringService::COLUMN_CURRENT_ASSESSMENT_ITEM,
                DeliveryMonitoringService::COLUMN_TEST_TAKER,
                DeliveryMonitoringService::COLUMN_AUTHORIZED_BY,
                DeliveryMonitoringService::COLUMN_START_TIME,
                DeliveryMonitoringService::COLUMN_END_TIME,
            ]
        );

        $this->getServiceManager()->register(DeliveryMonitoringService::CONFIG_ID, $deliveryMonitoringService);
    }
}

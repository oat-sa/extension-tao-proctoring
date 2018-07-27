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
namespace oat\taoProctoring\scripts\install;

use Doctrine\DBAL\Schema\SchemaException;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;

class RegisterProctoringLog extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $persistenceId = count($params) > 0 ? reset($params) : 'default';
        $persistence = \common_persistence_Manager::getPersistence($persistenceId);
        
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        
        try {
            $tableLog = $schema->createTable(RdsDeliveryLogService::TABLE_NAME);
            $tableLog->addOption('engine', 'InnoDB');
            $tableLog->addColumn(RdsDeliveryLogService::ID, "integer", array("autoincrement" => true));
            $tableLog->addColumn(RdsDeliveryLogService::DELIVERY_EXECUTION_ID, "string", array("notnull" => true, "length" => 255));
            $tableLog->addColumn(RdsDeliveryLogService::EVENT_ID, "string", array("notnull" => true, "length" => 255));
            $tableLog->addColumn(RdsDeliveryLogService::DATA, "text", array("notnull" => true));
            $tableLog->addColumn(RdsDeliveryLogService::CREATED_AT, "string", array("notnull" => true, "length" => 255));
            $tableLog->addColumn(RdsDeliveryLogService::CREATED_BY, "string", array("notnull" => true, "length" => 255));
        
            $tableLog->setPrimaryKey(array(RdsDeliveryLogService::ID));
        
            $tableLog->addIndex(
                array(RdsDeliveryLogService::DELIVERY_EXECUTION_ID),
                'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::DELIVERY_EXECUTION_ID
            );

            $tableLog->addIndex(
                array(RdsDeliveryLogService::EVENT_ID),
                'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::EVENT_ID
            );

            $tableLog->addIndex(
                array(RdsDeliveryLogService::CREATED_AT),
                'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::CREATED_AT
            );

            $tableLog->addIndex(
                array(RdsDeliveryLogService::CREATED_BY),
                'IDX_' . RdsDeliveryLogService::TABLE_NAME . '_' . RdsDeliveryLogService::CREATED_BY
            );

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }
        
        $this->registerService(
            RdsDeliveryLogService::SERVICE_ID,
            new RdsDeliveryLogService(array(
                RdsDeliveryLogService::OPTION_PERSISTENCE => $persistenceId,
                RdsDeliveryLogService::OPTION_FIELDS => [
                    RdsDeliveryLogService::EVENT_ID,
                    RdsDeliveryLogService::CREATED_BY,
                    RdsDeliveryLogService::DELIVERY_EXECUTION_ID,
                ]
            ))
        );
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Registered proctoring log'));
    }
}

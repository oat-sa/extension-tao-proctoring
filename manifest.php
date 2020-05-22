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
 */

use oat\taoProctoring\scripts\update\Updater;
use oat\tao\model\user\TaoRoles;
use oat\taoProctoring\controller\DeliverySelection;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\controller\MonitorProctorAdministrator;
use oat\taoProctoring\controller\Tools;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\scripts\install\OverrideSectionPauseService;
use oat\taoProctoring\scripts\install\RegisterAuthProvider;
use oat\taoProctoring\scripts\install\RegisterBreadcrumbsServices;
use oat\taoProctoring\scripts\install\RegisterDeleteDeliveryExecution;
use oat\taoProctoring\scripts\install\RegisterDeliveryExecutionManagerService;
use oat\taoProctoring\scripts\install\RegisterDeliveryServerService;
use oat\taoProctoring\scripts\install\RegisterGuiSettingsService;
use oat\taoProctoring\scripts\install\RegisterProctorAttemptService;
use oat\taoProctoring\scripts\install\RegisterProctoringDeliveryDeleteService;
use oat\taoProctoring\scripts\install\RegisterProctoringEntryPoint;
use oat\taoProctoring\scripts\install\RegisterProctoringLog;
use oat\taoProctoring\scripts\install\RegisterProctoringRunnerService;
use oat\taoProctoring\scripts\install\RegisterReasonCategoryService;
use oat\taoProctoring\scripts\install\RegisterRunnerMessageService;
use oat\taoProctoring\scripts\install\RegisterServices;
use oat\taoProctoring\scripts\install\RegisterWebhookEvents;
use oat\taoProctoring\scripts\install\SetupDeliveryMonitoring;
use oat\taoProctoring\scripts\install\SetupProctorCsvImporter;
use oat\taoProctoring\scripts\install\SetupProctoringEventListeners;
use oat\taoProctoring\scripts\install\SetUpProctoringUrlService;
use oat\taoProctoring\scripts\install\SetUpQueueTasks;
use oat\taoProctoring\scripts\uninstall\RestoreServices;
use oat\taoProctoring\scripts\uninstall\UnregisterProctoringEvents;

return [
    'name' => 'taoProctoring',
    'label' => 'Proctoring',
    'description' => 'Proctoring for deliveries',
    'license' => 'GPL-2.0',
    'version' => '19.6.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'tao' => '>=41.9.1',
        'taoDelivery' => '>=13.1.2',
        'taoDeliveryRdf' => '>=7.0.0',
        'taoTestTaker' => '>=4.0.0',
        'taoQtiTest' => '>=37.6.1',
        'taoOutcomeUi' => '>=7.0.0',
        'taoEventLog' => '>=2.0.0',
        'generis' => '>=12.15.0',
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',
    'acl' => [
        [
            'grant',
            'http://www.tao.lu/Ontologies/TAO.rdf#GlobalManagerRole',
            ['ext' => 'taoProctoring', 'mod' => 'Irregularity'],
        ],
        ['grant', ProctorService::ROLE_PROCTOR, DeliverySelection::class],
        ['grant', ProctorService::ROLE_PROCTOR, Monitor::class],
        ['grant', ProctorService::ROLE_PROCTOR, tao_actions_Breadcrumbs::class],
        ['grant', ProctorService::ROLE_PROCTOR, ['ext' => 'taoProctoring', 'mod' => 'Reporting']],
        ['grant', ProctorService::ROLE_PROCTOR, ['ext' => 'taoProctoring', 'mod' => 'TextConverter']],
        ['grant', TaoRoles::DELIVERY, ['ext' => 'taoProctoring', 'mod' => 'DeliveryServer']],
        ['grant', TaoRoles::SYSTEM_ADMINISTRATOR, Tools::class . '@pauseActiveExecutions'],
        ['grant', TaoRoles::OPERATIONAL_ADMINISTRATOR, ['ext' => 'taoProctoring', 'mod' => 'Tools']],
        ['grant', ProctorService::ROLE_PROCTOR_ADMINISTRATOR, MonitorProctorAdministrator::class],
    ],
    'install' => [
        'php' => [
            RegisterProctoringEntryPoint::class,
            SetupDeliveryMonitoring::class,
            RegisterProctoringLog::class,
            RegisterDeliveryServerService::class,
            SetupProctoringEventListeners::class,
            RegisterAuthProvider::class,
            RegisterServices::class,
            RegisterBreadcrumbsServices::class,
            RegisterReasonCategoryService::class,
            SetUpProctoringUrlService::class,
            RegisterRunnerMessageService::class,
            RegisterGuiSettingsService::class,
            RegisterDeliveryExecutionManagerService::class,
            OverrideSectionPauseService::class,
            RegisterProctoringRunnerService::class,
            SetupProctorCsvImporter::class,
            RegisterProctorAttemptService::class,
            RegisterProctoringDeliveryDeleteService::class,
            SetUpQueueTasks::class,
            RegisterDeleteDeliveryExecution::class,
            RegisterWebhookEvents::class,
        ],
        'rdf' => [
            __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'proctoring.rdf',
        ],
    ],
    'uninstall' => [
        'php' => [
            RestoreServices::class,
            UnregisterProctoringEvents::class,
        ],
    ],
    'routes' => [
        'taoProctoring' => 'oat\\taoProctoring\\controller',
    ],
    'update' => Updater::class,
    'constants' => [
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoProctoring/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ],
];

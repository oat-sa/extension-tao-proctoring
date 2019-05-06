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
use oat\tao\model\user\TaoRoles;
use oat\taoProctoring\controller\DeliverySelection;
use oat\taoProctoring\controller\Monitor;
use oat\taoProctoring\controller\MonitorProctorAdministrator;
use oat\taoProctoring\controller\Tools;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\scripts\install\RegisterAuthProvider;
use oat\taoProctoring\scripts\install\RegisterBreadcrumbsServices;
use oat\taoProctoring\scripts\install\RegisterDeliveryExecutionManagerService;
use oat\taoProctoring\scripts\install\RegisterDeliveryServerService;
use oat\taoProctoring\scripts\install\RegisterGuiSettingsService;
use oat\taoProctoring\scripts\install\RegisterProctoringDeliveryDeleteService;
use oat\taoProctoring\scripts\install\RegisterProctoringEntryPoint;
use oat\taoProctoring\scripts\install\RegisterProctoringLog;
use oat\taoProctoring\scripts\install\RegisterReasonCategoryService;
use oat\taoProctoring\scripts\install\RegisterRunnerMessageService;
use oat\taoProctoring\scripts\install\RegisterServices;
use oat\taoProctoring\scripts\install\SetupDeliveryMonitoring;
use oat\taoProctoring\scripts\install\SetupProctoringEventListeners;
use oat\taoProctoring\scripts\install\SetUpProctoringUrlService;
use oat\taoProctoring\scripts\install\SetUpQueueTasks;
use oat\taoProctoring\scripts\uninstall\RestoreServices;
use oat\taoProctoring\scripts\uninstall\UnregisterProctoringEvents;

return array(
    'name' => 'taoProctoring',
    'label' => 'Proctoring',
    'description' => 'Proctoring for deliveries',
    'license' => 'GPL-2.0',
    'version' => '12.6.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'tao'            => '>=21.8.0',
        'taoDelivery'    => '>=12.0.0',
        'taoDeliveryRdf' => '>=7.0.0',
        'taoTestTaker'   => '>=4.0.0',
        'taoQtiTest'     => '>=29.2.0',
        'taoOutcomeUi'   => '>=7.0.0',
        'taoEventLog'    => '>=2.0.0',
        'generis'        => '>=7.11.0',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#GlobalManagerRole', array('ext' => 'taoProctoring', 'mod'=>'Irregularity')),
        array('grant', ProctorService::ROLE_PROCTOR, DeliverySelection::class),
        array('grant', ProctorService::ROLE_PROCTOR, Monitor::class),
        array('grant', ProctorService::ROLE_PROCTOR, \tao_actions_Breadcrumbs::class),
        array('grant', ProctorService::ROLE_PROCTOR, array('ext'=>'taoProctoring', 'mod'=>'Reporting')),
        array('grant', ProctorService::ROLE_PROCTOR, array('ext'=>'taoProctoring', 'mod'=>'TextConverter')),
        array('grant', TaoRoles::DELIVERY, array('ext'=>'taoProctoring', 'mod'=>'DeliveryServer')),
        array('grant', TaoRoles::SYSTEM_ADMINISTRATOR, Tools::class.'@pauseActiveExecutions'),
        array('grant', TaoRoles::OPERATIONAL_ADMINISTRATOR, array('ext'=>'taoProctoring', 'mod'=>'Tools')),
        array('grant', ProctorService::ROLE_PROCTOR_ADMINISTRATOR, MonitorProctorAdministrator::class),
    ),
    'install' => array(
        'php' => array(
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
            \oat\taoProctoring\scripts\install\OverrideSectionPauseService::class,
            \oat\taoProctoring\scripts\install\RegisterProctoringRunnerService::class,
            \oat\taoProctoring\scripts\install\SetupProctorCsvImporter::class,
            \oat\taoProctoring\scripts\install\RegisterProctorAttemptService::class,
            RegisterProctoringDeliveryDeleteService::class,
            SetUpQueueTasks::class
        ),
        'rdf' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'proctoring.rdf'
        )
    ),
    'uninstall' => array(
        'php' => [
            RestoreServices::class,
            UnregisterProctoringEvents::class
        ]
    ),
    'routes' => array(
        'taoProctoring' => 'oat\\taoProctoring\\controller'
    ),
    'update' => 'oat\\taoProctoring\\scripts\\update\\Updater',
	'constants' => array(
	    # views directory
	    "DIR_VIEWS" => dirname(__FILE__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

		#BASE URL (usually the domain root)
		'BASE_URL' => ROOT_URL.'taoProctoring/',
	),
    'extra' => array(
        'structures' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    )
);

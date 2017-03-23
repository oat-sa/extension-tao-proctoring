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
use oat\taoProctoring\controller\Tools;
use oat\taoProctoring\model\ProctorService;
use oat\taoProctoring\scripts\install\RegisterAuthProvider;
use oat\taoProctoring\scripts\install\RegisterBreadcrumbsServices;
use oat\taoProctoring\scripts\install\RegisterDeliveryServerService;
use oat\taoProctoring\scripts\install\RegisterProctoringEntryPoint;
use oat\taoProctoring\scripts\install\RegisterProctoringLog;
use oat\taoProctoring\scripts\install\RegisterReasonCategoryService;
use oat\taoProctoring\scripts\install\RegisterRunnerMessageService;
use oat\taoProctoring\scripts\install\RegisterServices;
use oat\taoProctoring\scripts\install\SetupDeliveryMonitoring;
use oat\taoProctoring\scripts\install\SetupProctoringEventListeners;
use oat\taoProctoring\scripts\install\SetUpProctoringUrlService;

return array(
    'name' => 'taoProctoring',
    'label' => 'Proctoring',
    'description' => 'Proctoring for deliveries',
    'license' => 'GPL-2.0',
    'version' => '4.12.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'tao' => '>=7.81.1',
        'taoDelivery' => '>=4.7.0',
        'taoDeliveryRdf' => '>=1.0',
        'taoTestTaker' => '>=2.6.0',
        'taoQtiTest' => '>=6.18.0',
        'taoOutcomeUi' => '>=2.6.6',
        'generis' => '>=3.13.2',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#GlobalManagerRole', array('ext' => 'taoProctoring', 'mod'=>'Irregularity')),
        array('grant', ProctorService::ROLE_PROCTOR, DeliverySelection::class),
        array('grant', ProctorService::ROLE_PROCTOR, Monitor::class),
        array('grant', ProctorService::ROLE_PROCTOR, \tao_actions_Breadcrumbs::class),
        array('grant', ProctorService::ROLE_PROCTOR, array('ext'=>'taoProctoring', 'mod'=>'Reporting')),
        array('grant', ProctorService::ROLE_PROCTOR, array('ext'=>'taoProctoring', 'mod'=>'TextConverter')),
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#DeliveryRole', array('ext'=>'taoProctoring', 'mod'=>'DeliveryServer')),
        array('grant', TaoRoles::SYSTEM_ADMINISTRATOR, Tools::class.'@pauseActiveExecutions'),
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
        ),
        'rdf' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'proctoring.rdf'
        )
    ),
    'uninstall' => array(
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

	    #BASE WWW required by JS
	    'BASE_WWW' => ROOT_URL.'taoProctoring/views/'
	),
    'extra' => array(
        'structures' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    )
);

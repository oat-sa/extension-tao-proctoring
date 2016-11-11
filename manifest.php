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
use oat\taoProctoring\scripts\install\RegisterAuthProvider;

return array(
    'name' => 'taoProctoring',
    'label' => 'Proctoring',
    'description' => 'Proctoring for deliveries',
    'license' => 'GPL-2.0',
    'version' => '3.7.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'tao' => '>=4.5.0',
        'taoDelivery' => '>=4.4.1',
        'taoDeliveryRdf' => '>=1.0',
        'taoTestTaker' => '>=2.6.0',
        'taoQtiTest' => '>=5.10.1',
        'taoOutcomeUi' => '>=2.6.6',
        'generis' => '>=2.15.0',
        'taoClientDiagnostic' => '>=1.6.0',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#GlobalManagerRole', array('ext' => 'taoProctoring', 'mod'=>'Irregularity')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager', array('ext' => 'taoProctoring', 'mod'=>'TestCenterManager')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterAdministratorRole', array('ext'=>'taoProctoring', 'mod'=>'ProctorManager')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Delivery')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Diagnostic')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Reporting')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'TestCenter')),
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#DeliveryRole', array('ext'=>'taoProctoring', 'mod'=>'DeliveryServer')),
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoClientDiagnosticManager', array('ext'=>'taoProctoring','mod' => 'DiagnosticChecker')),
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole', array('ext'=>'taoProctoring','mod' => 'DiagnosticChecker')),
    ),
    'install' => array(
        'php' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'registerEntryPoint.php',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'createDeliveryMonitoringTables.php',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'registerCacheListener.php',
            'oat\\taoProctoring\\scripts\\install\\RegisterProctoringLog',
            'oat\\taoProctoring\\scripts\\install\\createDiagnosticTable',
            'oat\\taoProctoring\\scripts\\install\\addDiagnosticSettings',
            'oat\\taoProctoring\\scripts\\install\\RegisterAssignmentService',
            'oat\\taoProctoring\\scripts\\install\\RegisterDeliveryServerService',
            'oat\\taoProctoring\\scripts\\install\\RegisterSessionStateListener',
            RegisterAuthProvider::class
        ),
        'rdf' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'proctor.rdf',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'taotestcenter.rdf',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'eligibility.rdf',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'taodelivery.rdf',
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

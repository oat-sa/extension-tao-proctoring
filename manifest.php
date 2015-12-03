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

return array(
    'name' => 'taoProctoring',
	'label' => 'Proctoring',
	'description' => 'Proctoring for deliveries',
    'license' => 'GPL-2.0',
    'version' => '0.6',
	'author' => 'Open Assessment Technologies SA',
	'requires' => array(
        'tao' => '>=2.8.0',
        'taoDelivery' => '>=2.7.0',
        'taoTestTaker' => '>=2.6.0',
	    'taoQtiTest' => '>=2.16.0',
	    'taoOutcomeUi' => '>=2.6.6',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterManager', array('ext' => 'taoProctoring', 'mod'=>'TestCenterManager')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Delivery')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Diagnostic')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'Reporting')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole', array('ext'=>'taoProctoring', 'mod'=>'TestCenter')),
        array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#DeliveryRole', array('ext'=>'taoProctoring', 'mod'=>'DeliveryServer')),
    ),
    'install' => array(
        'php' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'registerEntryPoint.php'
        ),
        'rdf' => array(
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'proctor.rdf',
            __DIR__.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'taotestcenter.rdf'
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
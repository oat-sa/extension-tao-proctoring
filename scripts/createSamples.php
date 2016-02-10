<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */
require_once __DIR__.'/../../tao/includes/raw_start.php';
$resources = [
    'testTakers' => [
        [
            'login' => 's1',
            'password' => 's1',
            'label' => 'billy.laporte',
            'firstName' => 'Billy',
            'lastName' => 'LaPorte',
        ], [
            'login' => 's2',
            'password' => 's2',
            'label' => 'steven.pommier',
            'firstName' => 'Steven',
            'lastName' => 'Pommier',
        ], [
            'login' => 's3',
            'password' => 's3',
            'label' => 'marco.botton',
            'firstName' => 'Marco',
            'lastName' => 'Botton',
        ], [
            'login' => 's4',
            'password' => 's4',
            'label' => 'mariah.mclachlan',
            'firstName' => 'Mariah',
            'lastName' => 'McLachlan',
        ], [
            'login' => 's5',
            'password' => 's5',
            'label' => 'valerie.liberty',
            'firstName' => 'Valerie',
            'lastName' => 'Liberty',
        ], [
            'login' => 's6',
            'password' => 's6',
            'label' => 'alicia.ryker',
            'firstName' => 'Alicia',
            'lastName' => 'Ryker',
        ]
    ],
    'users' => [
        [
            'login' => 'proctor',
            'password' => '12345a',
            'label' => 'Proctor',
            'role' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#ProctorRole'
        ], [
            'login' => 'proctora',
            'password' => '12345a',
            'label' => 'Proctor Admin',
            'role' => 'http://www.tao.lu/Ontologies/TAOProctor.rdf#TestCenterAdministratorRole'
        ],
    ],
    'groups' => [
        [
            'label' => 'all',
            'users' => ['s1', 's2', 's3', 's4', 's5', 's6'],
        ], [
            'label' => 'g1',
            'users' => ['s1'],
        ], [
            'label' => 'g2',
            'users' => ['s2'],
        ], [
            'label' => 'g3',
            'users' => ['s3'],
        ], [
            'label' => 'g4',
            'users' => ['s4'],
        ], [
            'label' => 'g5',
            'users' => ['s5'],
        ], [
            'label' => 'g6',
            'users' => ['s6'],
        ]
    ]
];
$users = [];
$groups = [];
//create sample test takers
if (is_array($resources['testTakers'])) {
    $testTakerCrudService = oat\taoTestTaker\models\CrudService::singleton();
    $testTakerClass = new core_kernel_classes_Class(('http://www.tao.lu/Ontologies/TAO.rdf#User'));
    echo 'Create test takers:' . "\n";
    foreach($resources['testTakers'] as $testTakerResource) {
        $data = [
            PROPERTY_USER_LOGIN => $testTakerResource['login'],
            PROPERTY_USER_PASSWORD => $testTakerResource['password'],
        ];
        if (isset($testTakerResource['label'])) {
            $data[RDFS_LABEL] = $testTakerResource['label'];
        }
        if (isset($testTakerResource['firstName'])) {
            $data[PROPERTY_USER_FIRSTNAME] = $testTakerResource['firstName'];
        }
        if (isset($testTakerResource['lastName'])) {
            $data[PROPERTY_USER_LASTNAME] = $testTakerResource['lastName'];
        }
        try {
            $testTaker = $testTakerCrudService->createFromArray($data);
            echo '::created test taker: ' . $testTakerResource['login'] . "\n";
        } catch (\common_exception_PreConditionFailure $e) {
            $instances = $testTakerClass->searchInstances(array(
                PROPERTY_USER_LOGIN => $testTakerResource['login']
            ), array(
                'recursive' => true,
                'like' => false
            ));
            if (count($instances)) {
                $testTaker = current($instances);
                echo '::loaded test taker: ' . $testTakerResource['login'] . "\n";
            } else {
                $testTaker = null;
                echo '::test taker not found: ' . $testTakerResource['login'] . "\n";
            }
        }
        if ($testTaker) {
            $users[$testTakerResource['login']] = $testTaker;
        }
    }
}
if (is_array($resources['users'])) {
    $userService = \tao_models_classes_UserService::singleton();
    $userClass = new \core_kernel_classes_Class(CLASS_TAO_USER);
    echo 'Create users:' . "\n";
    foreach($resources['users'] as $userResource) {
        try {
            $user = $userService->addUser(
                $userResource['login'],
                $userResource['password'],
                new \core_kernel_classes_Resource($userResource['role']),
                $userClass
            );
            echo '::created user: ' . $userResource['login'] . "\n";
        } catch (\core_kernel_users_Exception $e) {
            $instances = $testTakerClass->searchInstances(array(
                PROPERTY_USER_LOGIN => $userResource['login']
            ), array(
                'recursive' => true,
                'like' => false
            ));
            if (count($instances)) {
                $user = current($instances);
                echo '::loaded user: ' . $userResource['login'] . "\n";
            } else {
                $user = null;
                echo '::user not found: ' . $userResource['login'] . "\n";
            }
        }
        if ($user) {
            $users[$userResource['login']] = $user;
        }
    }
}
if (is_array($resources['groups'])) {
    $groupCrudService = oat\taoGroups\models\CrudGroupsService::singleton();
    $groupService = oat\taoGroups\models\GroupsService::singleton();
    $groupClass = $groupService->getRootClass();
    echo 'Create groups:' . "\n";
    foreach($resources['groups'] as $groupResource) {
        $instances = $groupClass->searchInstances(array(
            RDFS_LABEL => $groupResource['label']
        ), array(
            'recursive' => true,
            'like' => false
        ));
        if (count($instances)) {
            $group = current($instances);
            echo '::loaded group: ' . $groupResource['label'] . "\n";
        } else {
            $group = $groupCrudService->createFromArray([
                RDFS_LABEL => $groupResource['label']
            ]);
            echo '::created group: ' . $groupResource['label'] . "\n";
        }
        if (isset($groupResource['users'])) {
            if (!is_array($groupResource['users'])) {
                $groupResource['users'] = [$groupResource['users']];
            }
            foreach($groupResource['users'] as $login) {
                if (isset($users[$login])) {
                    $groupService->addUser($users[$login]->getUri(), $group);
                }
                $user = $users[$login];
                echo '  ::added user: ' . $login . "\n";
            }
        }
        $users[$groupResource['label']] = $group;
    }
}
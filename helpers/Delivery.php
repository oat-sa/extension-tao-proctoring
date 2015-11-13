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

namespace oat\taoProctoring\helpers;

use oat\oatbox\user\User;
use oat\oatbox\service\ServiceManager;
use \core_kernel_classes_Resource;
use \common_session_SessionManager;
use oat\taoProctoring\model\mock\WebServiceMock;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class Delivery extends Proctoring
{
    /**
     * Gets a list of available deliveries for a test site
     *
     * @param string $testCenter
     * @param array [$options]
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getDeliveries($testCenter)
    {
        $currentUser = common_session_SessionManager::getSession()->getUser();
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        $entries = array();

        $all = array(
            'id' => 'all',
            'url' => _url('monitoringAll', 'Delivery', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('All Deliveries'),
            'cls' => 'dark',
            'stats' => array(
                'awaitingApproval' => 0,
                'inProgress' => 0,
                'paused' => 0
            )
        );

        $mocks = array(
            array(
                'stats' => array(
                    'awaitingApproval' => 0,
                    'inProgress' => 0,
                    'paused' => 0
                ),
                'properties' => array(),
            ),
            array(
                'stats' => array(
                    'awaitingApproval' => 3,
                    'inProgress' => 32,
                    'paused' => 12
                ),
                'properties' => array(
                    'periodStart' => '2015-11-09 00:00',
                    'periodEnd' => '2015-11-17 09:20'
                )
            ),
            array(
                'stats' => array(
                    'awaitingApproval' => 0,
                    'inProgress' => 15,
                    'paused' => 1
                ),
                'properties' => array(
                    'periodStart' => '2015-11-09 00:00',
                    'periodEnd' => '2015-11-17 09:20'
                )
            ),
            array(
                'stats' => array(
                    'awaitingApproval' => 1,
                    'inProgress' => 10,
                    'paused' => 8
                ),
                'properties' => array(
                    'periodStart' => '2015-11-09 00:00',
                    'periodEnd' => '2015-11-17 09:20'
                )
            ),
        );

        foreach ($deliveries as $delivery) {
            $entries[] = array_merge(array(
                'id' => $delivery->getUri(),
                'url' => _url('monitoring', 'Delivery', null, array('delivery' => $delivery->getUri(), 'testCenter' => $testCenter->getUri())),
                'label' => $delivery->getLabel(),
                'text' => __('Monitor')
            ), WebServiceMock::random($mocks));
        }

        $all = array_reduce($entries, function($carry, $element){
            $carry['stats']['awaitingApproval'] += $element['stats']['awaitingApproval'];
            $carry['stats']['inProgress'] += $element['stats']['inProgress'];
            $carry['stats']['paused'] += $element['stats']['paused'];
            return $carry;
        }, $all);

        //prepend the all delivery element to the begining of the array
        array_unshift($entries, $all);
        return $entries;
    }

    /**
     * Gets a delivery
     *
     * @param string $deliveryId
     * @return core_kernel_classes_Resource
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getDelivery($deliveryId)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        return $deliveryService->getDelivery($deliveryId);
    }

    /**
     * Gets the aggregated data for a filtered set of delivery executions of a given delivery
     * This is performance critical, would need to find a way to optimize to obtain such information
     *
     * @param $deliveryId
     * @param array $options
     * @return array
     * @throws \Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getCurrentDeliveryExecutions($deliveryId, $options = array()) {
        if (is_object($deliveryId)) {
            $delivery = self::getDelivery($deliveryId);

            if (!$delivery) {
                throw new \Exception('Unknown delivery!');
            }
        } else {
            $delivery = $deliveryId;
            $deliveryId = $delivery->getUri();
        }

        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $deliveryExecutions = $deliveryService->getCurrentDeliveryExecutions($deliveryId, $options);
        $testTakers = self::collectionToMap($deliveryService->getDeliveryTestTakers($deliveryId, $options));

        $page = self::paginate($deliveryExecutions, $options);

        /**** to be replaced by real stuff ****/
        // Seeds the random number generator with a fixed value to avoid changes on refresh
        srand(count($deliveryExecutions));
        // Sets some mock data for delivery states
        $mocks = array(
            array(
                //client will infer possible action based on the current status
                'section' => array(
                    'label' => 'section B',
                    'position' => 2,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 1,
                    'total' => 9,
                    'time' => array(
                        //time unit in second, does not require microsecond precision for human monitoring
                        'elapsed' => 340,
                        'total' => 600
                    )
                )
            ),
            array(
                'section' => array(
                    'label' => 'section A',
                    'position' => 1,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question Y',
                    'position' => 5,
                    'total' => 8,
                    'time' => array(
                        'elapsed' => 60,
                        'total' => 600
                    )
                )
            ),
            array(
                'section' => array(
                    'label' => 'section C',
                    'position' => 3,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question Z',
                    'position' => 2,
                    'total' => 4,
                    'time' => array(
                        'elapsed' => 540,
                        'total' => 600
                    )
                )
            ),
        );
        /********/

        $executions = array();
        foreach($page['data'] as $deliveryExecution) {
            /* @var $deliveryExecution DeliveryExecution */
            $userId = $deliveryExecution->getUserIdentifier();
            $executionState = $deliveryExecution->getState();
            $status = $executionState->getLabel();
            $state = array(
                'status' => $status,
            );
            $testTaker = array();

            /**** to be replaced by real stuff ****/
            $state = array_merge($state, WebServiceMock::random($mocks));
            /********/

            $user = isset($testTakers[$userId]) ? $testTakers[$userId] : null;
            if ($user) {
                /* @var $user User */
                $firstName = self::getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
                $lastName = self::getUserStringProp($user, PROPERTY_USER_LASTNAME);

                if (empty($firstName) && empty($lastName)) {
                    $firstName = self::getUserStringProp($user, RDFS_LABEL);
                }

                $testTaker['id'] = $user->getIdentifier();
                $testTaker['firstName'] = $firstName;
                $testTaker['lastName'] = $lastName;
            }

            $executions[] = array(
                'id' => $deliveryExecution->getIdentifier(),
                'delivery' => array(
                    'label' => $deliveryExecution->getLabel(),
                ),
                'testTaker' => $testTaker,
                'state' => $state,
            );
        }

        $page['data'] = $executions;

        return $page;
    }

    /**
     * Gets all deliveries executions from the current test center
     * This is performance critical, would need to find a way to optimize to obtain such information
     *
     * @param $testCenter
     * @param array $options
     * @return array
     * @throws \Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getAllCurrentDeliveriesExecutions($testCenter, $options = array()){
        $currentUser = common_session_SessionManager::getSession()->getUser();
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        if (count($deliveries)) {
            $all = array();
            foreach($deliveries as $delivery) {
                $executions = self::getCurrentDeliveryExecutions($delivery);
                if (isset($executions['data'])) {
                    foreach($executions['data'] as $execution) {
                        $execution['delivery'] = array(
                            'uri' => $delivery->getUri(),
                            'label' => $delivery->getLabel(),
                        );
                        $all[] = $execution;
                    }
                }
            }

            return self::paginate($all, $options);
        } else {
            return self::paginate(array(), $options);
        }
    }

    /**
     * Gets the list of test takers assigned to a delivery
     *
     * @param string $deliveryId
     * @param array [$options]
     * @return array
     * @throws \Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getDeliveryTestTakers($deliveryId, $options = array())
    {
        if (is_object($deliveryId)) {
            $delivery = self::getDelivery($deliveryId);

            if (!$delivery) {
                throw new \Exception('Unknown delivery!');
            }
        } else {
            $delivery = $deliveryId;
            $deliveryId = $delivery->getUri();
        }

        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $users = $deliveryService->getDeliveryTestTakers($deliveryId, $options);
        $deliveryExecutions = $deliveryService->getCurrentDeliveryExecutions($deliveryId, $options);
        $usersStatus = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            $userId = $deliveryExecution->getUserIdentifier();
            $status = $deliveryExecution->getState()->getLabel();
            $usersStatus[$userId][] = $status;
        }

        $page = self::paginate($users, $options);

        $testTakers = array();
        foreach($page['data'] as $user) {
            /* @var $user User */
            $firstName = self::getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = self::getUserStringProp($user, PROPERTY_USER_LASTNAME);

            if (empty($firstName) && empty($lastName)) {
                $firstName = self::getUserStringProp($user, RDFS_LABEL);
            }

            $status = '';
            if (isset($usersStatus[$user->getIdentifier()])) {
                $status = implode(', ', array_unique($usersStatus[$user->getIdentifier()]));
            }

            $testTakers[] = array(
                'id' => $user->getIdentifier(),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'identifier' => $user->getIdentifier(),
                'status' => $status,
            );
        }

        $page['data'] = $testTakers;

        return $page;
    }

    /**
     * Gets the list of test takers assigned to all deliveries
     *
     * @param $testCenter
     * @param array [$options]
     * @return array
     */
    public static function getAllDeliveryTestTakers($testCenter, $options = array()){
        $currentUser = common_session_SessionManager::getSession()->getUser();
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $deliveries = $deliveryService->getProctorableDeliveries($currentUser);

        if (count($deliveries)) {
            $all = array();
            foreach($deliveries as $delivery) {
                $testTakers = self::getDeliveryTestTakers($delivery);
                if (isset($testTakers['data'])) {
                    foreach($testTakers['data'] as $testTaker) {
                        $testTaker['delivery'] = array(
                            'uri' => $delivery->getUri(),
                            'label' => $delivery->getLabel(),
                        );
                        $all[] = $testTaker;
                    }
                }
            }

            return self::paginate($all, $options);
        } else {
            return self::paginate(array(), $options);
        }
    }

    /**
     * Gets the test takers available for a delivery as a table page
     *
     * @param string $deliveryId
     * @param array [$options]
     * @return array
     * @throws \Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getAvailableTestTakers($deliveryId, $options = array())
    {
        $currentUser = common_session_SessionManager::getSession()->getUser();
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $users = $deliveryService->getAvailableTestTakers($currentUser, $deliveryId, $options);

        $page = self::paginate($users, $options);

        $testTakers = array();
        foreach($page['data'] as $user) {
            /* @var $user User */
            $firstName = self::getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = self::getUserStringProp($user, PROPERTY_USER_LASTNAME);

            if (empty($firstName) && empty($lastName)) {
                $firstName = self::getUserStringProp($user, RDFS_LABEL);
            }

            $testTakers[] = array(
                'id' => $user->getIdentifier(),
                'firstname' => $firstName,
                'lastname' => $lastName,
                'identifier' => $user->getIdentifier(),
            );
        }

        $page['data'] = $testTakers;

        return $page;
    }
}
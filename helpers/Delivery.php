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
use oat\taoProctoring\model\implementation\DeliveryService;
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

        foreach ($deliveries as $delivery) {
            /* @var $delivery \taoDelivery_models_classes_DeliveryRdf */
            $executions = $deliveryService->getCurrentDeliveryExecutions($delivery->getUri());
            $inprogress = 0;
            $paused = 0;
            $awaiting = 0;
            foreach($executions as $execution) {
                /* @var $execution DeliveryExecution */
                $executionState = $deliveryService->getState($execution);
                switch($executionState){
                    case DeliveryService::STATE_AWAITING:
                        $awaiting++;
                        break;
                    case DeliveryService::STATE_INPROGRESS:
                        $inprogress++;
                        break;
                    case DeliveryService::STATE_PAUSED:
                        $paused++;
                        break;
                    default:
                        continue;
                }
            }

            $deliveryProperties = $deliveryService->getDeliveryProperties($delivery);
            $properties = array();

            if (!empty($deliveryProperties[TAO_DELIVERY_START_PROP])) {
                $properties['periodStart'] = date('Y-m-d H:i:s', $deliveryProperties[TAO_DELIVERY_START_PROP]);
            }
            if (!empty($deliveryProperties[TAO_DELIVERY_END_PROP])) {
                $properties['periodEnd'] = date('Y-m-d H:i:s', $deliveryProperties[TAO_DELIVERY_END_PROP]);
            }

            $entries[] = array(
                'id' => $delivery->getUri(),
                'url' => _url('monitoring', 'Delivery', null, array('delivery' => $delivery->getUri(), 'testCenter' => $testCenter->getUri())),
                'label' => $delivery->getLabel(),
                'text' => __('Monitor'),
                'stats' => array(
                    'awaitingApproval' => $awaiting,
                    'inProgress' => $inprogress,
                    'paused' => $paused
                ),
                'properties' => $properties
            );

            $all['stats']['awaitingApproval'] += $awaiting;
            $all['stats']['inProgress'] += $inprogress;
            $all['stats']['paused'] += $paused;
        }

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

        $page = self::paginate($deliveryExecutions, $options);
        $page['data'] = self::adjustDeliveryExecutions($delivery, $page['data']);

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
                $deliveryExecutions = $deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $options);
                $all = array_merge($all, self::adjustDeliveryExecutions($delivery, $deliveryExecutions));
            }

            usort($all, array('\\oat\\taoProctoring\\helpers\\Delivery', 'sortExecutionsByState'));

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

    /**
     * Add a list of test takers to a delivery.
     * Returns the list of successfully added test takers.
     *
     * @param array $testTakers
     * @param string $deliveryId
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function assignTestTakers($testTakers, $deliveryId)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($testTakers as $testTaker) {
            if ($deliveryService->assignTestTaker($testTaker, $deliveryId)) {
                $result[] = $testTaker;
            }
        }

        return $result;
    }

    /**
     * Removes a list of test takers from a delivery.
     * Returns the list of successfully removed test takers.
     *
     * @param array $testTakers
     * @param string $deliveryId
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function unassignTestTakers($testTakers, $deliveryId)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($testTakers as $testTaker) {
            if ($deliveryService->unassignTestTaker($testTaker, $deliveryId)) {
                $result[] = $testTaker;
            }
        }

        return $result;
    }

    /**
     * Authorises a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function authoriseExecutions($deliveryExecutions, $reason = null)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->authoriseExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution;
            }
        }

        return $result;
    }

    /**
     * Terminates a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function terminateExecutions($deliveryExecutions, $reason = null)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->terminateExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution;
            }
        }

        return $result;
    }

    /**
     * Pauses a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function pauseExecutions($deliveryExecutions, $reason = null)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->pauseExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution;
            }
        }

        return $result;
    }

    /**
     * Report irregularity to a list of delivery executions
     *
     * @param array $deliveryExecutions
     * @param array $reason
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function reportExecutions($deliveryExecutions, $reason = null)
    {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->reportExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution;
            }
        }

        return $result;
    }

    /**
     * Sort delivery executions by state.
     * Not to be used on DeliveryExecution instances. This is only designed to sort adjusted array of executions.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function sortExecutionsByState($a, $b)
    {
        $allowedStates = Delivery::getAllowedStates();
        $aState = isset($allowedStates[$a['state']['status']]) ? $allowedStates[$a['state']['status']] : 0;
        $bState = isset($allowedStates[$b['state']['status']]) ? $allowedStates[$b['state']['status']] : 0;
        $result = $aState - $bState;

        if (!$result) {
            $result = strcmp($a['date'], $b['date']);
        }

        if (!$result) {
            $result = strcmp($a['delivery']['label'], $b['delivery']['label']);
        }

        return $result;
    }

    /**
     * Hash table for allowed states
     * @var array
     */
    private static $allowedStates;

    /**
     * Gets a hash table to translate allowed states in state numerical identifier (position for sorting)
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    private static function getAllowedStates()
    {
        if (!self::$allowedStates) {
            $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
            self::$allowedStates = array_flip($deliveryService->getAllowedStates());
        }
        return self::$allowedStates;
    }

    /**
     * Adjusts a list of delivery executions: add information, format the result
     *
     * @param core_kernel_classes_Resource$delivery
     * @param DeliveryExecution[] $deliveryExecutions
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    private static function adjustDeliveryExecutions($delivery, $deliveryExecutions) {
        $deliveryService = ServiceManager::getServiceManager()->get('taoProctoring/delivery');
        $testTakers = self::collectionToMap($deliveryService->getDeliveryTestTakers($delivery->getUri()));

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
        foreach($deliveryExecutions as $deliveryExecution) {

            $userId = $deliveryExecution->getUserIdentifier();
            $state = array(
                'status' => $deliveryService->getState($deliveryExecution),
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
                    'uri' => $delivery->getUri(),
                    'label' => $delivery->getLabel(),
                ),
                'date' => date('Y-m-d H:i:s', $deliveryService->getStartTime($deliveryExecution)),
                'testTaker' => $testTaker,
                'state' => $state,
            );
        }

        return $executions;
    }
}
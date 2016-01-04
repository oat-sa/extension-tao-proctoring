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
use core_kernel_classes_Resource;
use oat\taoProctoring\model\EligibilityService;
use oat\taoProctoring\model\mock\WebServiceMock;
use oat\taoDelivery\models\classes\execution\DeliveryExecution;
use oat\taoProctoring\model\implementation\DeliveryService;
use tao_helpers_Date as DateHelper;
use oat\tao\helpers\UserHelper;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class DeliveryHelper
{
    /**
     * Gets a list of available deliveries for a test site
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param array [$options]
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public static function getDeliveries(core_kernel_classes_Resource $testCenter)
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $deliveries = EligibilityService::singleton()->getEligibleDeliveries($testCenter);

        $entries = array();

        $all = array(
            'id' => 'all',
            'url' => _url('monitoringAll', 'Delivery', null, array('testCenter' => $testCenter->getUri())),
            'label' => __('All Sessions'),
            'cls' => 'dark',
            'stats' => array(
                'awaitingApproval' => 0,
                'inProgress' => 0,
                'paused' => 0
            )
        );

        foreach ($deliveries as $delivery) {
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
                $properties['periodStart'] = DateHelper::displayeDate($deliveryProperties[TAO_DELIVERY_START_PROP]);
            }
            if (!empty($deliveryProperties[TAO_DELIVERY_END_PROP])) {
                $properties['periodEnd'] = DateHelper::displayeDate($deliveryProperties[TAO_DELIVERY_END_PROP]);
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        return self::adjustDeliveryExecutions($deliveryService->getCurrentDeliveryExecutions($deliveryId, $options), $options);
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $deliveries = EligibilityService::singleton()->getEligibleDeliveries($testCenter);

        $all = array();
        foreach($deliveries as $delivery) {
            if ($delivery->exists()) {
                $all = array_merge($all, $deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $options));
            }
        }

        return self::adjustDeliveryExecutions($all, $options);
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $users = $deliveryService->getDeliveryTestTakers($deliveryId, $options);
        $delivery = self::getDelivery($deliveryId);

        return DataTableHelper::paginate($users, $options, function($users) use ($delivery) {
            $executionService = \taoDelivery_models_classes_execution_ServiceProxy::singleton();

            $testTakers = array();
            foreach($users as $user) {
                /* @var $user User */
                $userId = $user->getIdentifier();
                $lastName = UserHelper::getUserLastName($user);
                $firstName = UserHelper::getUserFirstName($user, empty($lastName));

                $status = array();
                $executions = $executionService->getUserExecutions($delivery, $userId);
                foreach ($executions as $execution) {
                    $status[] = $execution->getState()->getLabel();
                }

                $testTakers[] = array(
                    'id' => $userId,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'identifier' => $userId,
                    'status' => implode(', ', array_unique($status)),
                );
            }
            return $testTakers;
        });
    }

    /**
     * Gets the test takers available for a delivery as a table page
     *
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $testCenter
     * @param array [$options]
     * @return array
     * @throws \Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getAvailableTestTakers($delivery, $testCenter, $options = array())
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $users = EligibilityService::singleton()->getEligibleTestTakers($testCenter, $delivery);
        $assignedUsers = $deliveryService->getDeliveryTestTakers($delivery->getUri(), $options);
        array_walk($assignedUsers, function(&$value){
            $value = $value->getIdentifier();
        });
        $users = array_diff($users, $assignedUsers);

        return DataTableHelper::paginate($users, $options, function($users) {
            $testTakers = array();
            foreach($users as $userId) {
                $user = new \core_kernel_users_GenerisUser(new core_kernel_classes_Resource($userId));
                $userId = $user->getIdentifier();
                $lastName = UserHelper::getUserLastName($user);
                $firstName = UserHelper::getUserFirstName($user, empty($lastName));

                $testTakers[] = array(
                    'id' => $userId,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'identifier' => $userId,
                );
            }
            return $testTakers;
        });
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

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
     * @param string $testCenter Test center uri
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function authoriseExecutions($deliveryExecutions, $reason = null, $testCenter = null)
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->authoriseExecution($deliveryExecution, $reason, $testCenter)) {
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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

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
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if ($deliveryService->reportExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution;
            }
        }

        return $result;
    }

    /**
     * Adjusts a list of delivery executions: add information, format the result
     *
     * @param DeliveryExecution[] $deliveryExecutions
     * @param array $options
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    private static function adjustDeliveryExecutions($deliveryExecutions, $options) {
        // sort all executions by reverse date
        usort($deliveryExecutions, function($a, $b) {
            return -strcmp(DateHelper::getTimeStamp($a->getStartTime()), DateHelper::getTimeStamp($b->getStartTime()));
        });

        // paginate, then format the data
        return DataTableHelper::paginate($deliveryExecutions, $options, function($deliveryExecutions) {
            $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

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
            
            
            $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
            foreach($deliveryExecutions as $deliveryExecution) {
                $cachedData = current($deliveryMonitoringService->find([
                    [DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]
                ], ['asArray' => true], true));
                
                $userId = $deliveryExecution->getUserIdentifier();
                $state = array(
                    'status' => $deliveryService->getState($deliveryExecution),
                    'description' => $cachedData[DeliveryMonitoringService::COLUMN_STATUS]
                );
                $testTaker = array();

                /**** to be replaced by real stuff ****/
                // $state = array_merge($state, WebServiceMock::random($mocks));
                /********/

                $user = UserHelper::getUser($userId);
                if ($user) {
                    /* @var $user User */
                    $testTaker['id'] = $user->getIdentifier();
                    $testTaker['lastName'] = UserHelper::getUserLastName($user);
                    $testTaker['firstName'] = UserHelper::getUserFirstName($user, empty($testTaker['lastName']));
                }

                $delivery = $deliveryExecution->getDelivery();
                $executions[] = array(
                    'id' => $deliveryExecution->getIdentifier(),
                    'delivery' => array(
                        'uri' => $delivery->getUri(),
                        'label' => $delivery->getLabel(),
                    ),
                    'date' => DateHelper::displayeDate($deliveryExecution->getStartTime()),
                    'testTaker' => $testTaker,
                    'state' => $state,
                );
            }

            return $executions;
        });
    }
}

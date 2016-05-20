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
use oat\taoProctoring\model\DeliveryExecutionStateService;
use tao_helpers_Date as DateHelper;
use oat\tao\helpers\UserHelper;
use oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoringService;
use oat\taoQtiTest\models\event\QtiTestChangeEvent;
use qtism\runtime\tests\AssessmentTestSessionState;
use oat\taoProctoring\model\TestSessionConnectivityStatusService;

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
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
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
            $executions = $deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $testCenter->getUri());
            $inprogress = 0;
            $paused = 0;
            $awaiting = 0;
            foreach($executions as $execution) {
                /* @var $execution DeliveryExecution */
                $executionState = $deliveryExecutionStateService->getState($execution);
                switch($executionState){
                    case DeliveryExecutionStateService::STATE_AWAITING:
                        $awaiting++;
                        break;
                    case DeliveryExecutionStateService::STATE_INPROGRESS:
                        $inprogress++;
                        break;
                    case DeliveryExecutionStateService::STATE_PAUSED:
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

        usort($entries, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

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
    public static function getCurrentDeliveryExecutions($deliveryId, $testCenterId, $options = array()) {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        return self::adjustDeliveryExecutions($deliveryService->getCurrentDeliveryExecutions($deliveryId, $testCenterId, $options), $options);
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
                $all = array_merge($all, $deliveryService->getCurrentDeliveryExecutions($delivery->getUri(), $testCenter->getUri(), $options));
            }
        }

        return self::adjustDeliveryExecutions($all, $options);
    }

    /**
     * Gets the list of test takers assigned to a delivery
     *
     * @param string $deliveryId
     * @param string $testCenterId
     * @param array [$options]
     * @return array
     * @throws \Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getDeliveryTestTakers($deliveryId, $testCenterId, $options = array())
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $users = $deliveryService->getDeliveryTestTakers($deliveryId, $testCenterId, $options);
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
     * @param string [$testCenterId]
     * @return array
     * @throws \Exception
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function getAvailableTestTakers($delivery, $testCenter, $options = array())
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);
        $users = EligibilityService::singleton()->getEligibleTestTakers($testCenter, $delivery);
        $assignedUsers = $deliveryService->getDeliveryTestTakers($delivery->getUri(), $testCenter->getUri(), $options);
        array_walk($assignedUsers, function(&$value){
            $value = $value->getIdentifier();
        });
        $users = array_diff($users, $assignedUsers);

        array_walk($users, function(&$user) {
            $user = new \core_kernel_users_GenerisUser(new \core_kernel_classes_Resource($user));
        });

        usort($users, function($a, $b) {
            return strcasecmp(
                UserHelper::getUserLastName($a),
                UserHelper::getUserLastName($b)
            );
        });

        return DataTableHelper::paginate($users, $options, function($users) {
            $testTakers = array();
            foreach($users as $user) {
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
     * @param string $testCenterId
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function assignTestTakers($testTakers, $deliveryId, $testCenterId)
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

        $result = array();
        foreach($testTakers as $testTaker) {
            if ($deliveryService->assignTestTaker($testTaker, $deliveryId, $testCenterId)) {
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
     * @param string $testCenterId
     * @return array
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public static function unassignTestTakers($testTakers, $deliveryId, $testCenterId)
    {
        $deliveryService = ServiceManager::getServiceManager()->get(DeliveryService::CONFIG_ID);

        $result = array();
        foreach($testTakers as $testTaker) {
            if ($deliveryService->unassignTestTaker($testTaker, $deliveryId, $testCenterId)) {
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
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }
            if ($deliveryExecutionStateService->authoriseExecution($deliveryExecution, $reason, $testCenter)) {
                $result[] = $deliveryExecution->getIdentifier();
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
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }
            if ($deliveryExecutionStateService->terminateExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution->getIdentifier();
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
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }
            if ($deliveryExecutionStateService->pauseExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution->getIdentifier();
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
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

        $result = array();
        foreach($deliveryExecutions as $deliveryExecution) {
            if (is_string($deliveryExecution)) {
                $deliveryExecution = self::getDeliveryExecutionById($deliveryExecution);
            }
            if ($deliveryExecutionStateService->reportExecution($deliveryExecution, $reason)) {
                $result[] = $deliveryExecution->getIdentifier();
            }
        }

        return $result;
    }

    public static function getDeliveryExecutionById($deliveryExecutionId)
    {
        return \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($deliveryExecutionId);
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
            $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);

            $executions = array();
            
            $deliveryMonitoringService = ServiceManager::getServiceManager()->get(DeliveryMonitoringService::CONFIG_ID);
            $testSessionConnectivityStatusService = ServiceManager::getServiceManager()->get(TestSessionConnectivityStatusService::SERVICE_ID);

            foreach($deliveryExecutions as $deliveryExecution) {
                $cachedData = current($deliveryMonitoringService->find([
                    [DeliveryMonitoringService::COLUMN_DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier()]
                ], ['asArray' => true], true));
                
                $userId = $deliveryExecution->getUserIdentifier();
                $state = array(
                    'status' => $deliveryExecutionStateService->getState($deliveryExecution),
                    'progress' => $cachedData[DeliveryMonitoringService::COLUMN_CURRENT_ASSESSMENT_ITEM]
                );
                $testTaker = array();
                $extraFields = array();
                
                $user = UserHelper::getUser($userId);
                if ($user) {
                    /* @var $user User */
                    $testTaker['id'] = $user->getIdentifier();
                    $testTaker['lastName'] = UserHelper::getUserLastName($user);
                    $testTaker['firstName'] = UserHelper::getUserFirstName($user, empty($testTaker['lastName']));
                    
                    $userExtraFields = self::_getUserExtraFields();
                    foreach($userExtraFields as $field){
                        $values = $user->getPropertyValues($field['property']);
                        if(!empty($values) && is_array($values)){
                            $extraFields[$field['id']] = (string) $values[0];
                        }
                    }
                }

                $online = $deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_ACTIVE &&
                          $testSessionConnectivityStatusService->isOnline($deliveryExecution->getIdentifier());

                $delivery = $deliveryExecution->getDelivery();
                $executions[] = array(
                    'id' => $deliveryExecution->getIdentifier(),
                    'delivery' => array(
                        'uri' => $delivery->getUri(),
                        'label' => $delivery->getLabel(),
                    ),
                    'date' => DateHelper::displayeDate($deliveryExecution->getStartTime()),
                    'testTaker' => $testTaker,
                    'extraFields' => $extraFields,
                    'state' => $state,
                    'online' => $online,
                );
            }

            return $executions;
        });
    }

    /**
     * Get array of user specific extra fields to be displayed in the monitoring data table
     * 
     * @return array
     */
    private static function _getUserExtraFields(){
        $returnValue = array();
        $proctoringExtension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
        $userExtraFields = $proctoringExtension->getConfig('monitoringUserExtraFields');
        if(!empty($userExtraFields) && is_array($userExtraFields)){
            foreach($userExtraFields as $name => $uri){
                $property = new \core_kernel_classes_Property($uri);
                $returnValue[] = array(
                    'id' => $name,
                    'property' => $property,
                    'label' => $property->getLabel()
                );
            }
        }
        return $returnValue;
    }

    /**
     * Return array of extra fields to be displayed in the monitoring data table
     * 
     * @return array
     */
    public static function getExtraFields(){
        return array_map(function($field){
            return array(
                'id' => $field['id'],
                'label' => $field['label']
            );
        }, self::_getUserExtraFields());
    }

     /**
     * Catch changing of session state
     * @param QtiTestChangeEvent $event
     */
    public static function testStateChanged(QtiTestChangeEvent $event)
    {
        /** @var \taoQtiTest_helpers_TestSession $session */
        if (method_exists($event, 'getSession')) {
            $session = $event->getSession();
    
            $state = $session->getState();
    
            if ($state === AssessmentTestSessionState::SUSPENDED) {
                self::setHasBeenPaused($session->getSessionId(), true);
            }
        }
    }

    /**
     * @param $deliveryExecution
     * @return mixed
     */
    public static function getHasBeenPaused($deliveryExecution)
    {
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $proctoringState = $deliveryExecutionStateService->getProctoringState($deliveryExecution);
        $status = $proctoringState['hasBeenPaused'];
        self::setHasBeenPaused($deliveryExecution, false);
        return $status;
    }

    /**
     * @param $deliveryExecution
     * @param boolean $paused
     */
    public static function setHasBeenPaused($deliveryExecution, $paused)
    {
        $deliveryExecutionStateService = ServiceManager::getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $proctoringState = $deliveryExecutionStateService->getProctoringState($deliveryExecution);
        $deliveryExecutionStateService->setProctoringState($deliveryExecution, $proctoringState['status'], $proctoringState['reason'], $paused);
    }
}

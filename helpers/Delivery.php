<?php
/**
 * Created by PhpStorm.
 * User: jsc
 * Date: 06/11/15
 * Time: 10:47
 */

namespace oat\taoProctoring\helpers;

use oat\oatbox\user\User;
use oat\oatbox\service\ServiceManager;
use \core_kernel_classes_Resource;
use \common_session_SessionManager;

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
        foreach ($deliveries as $delivery) {
            $entries[] = array(
                'id' => $delivery->getUri(),
                'url' => _url('monitoring', 'Delivery', null, array('delivery' => $delivery->getUri(), 'testCenter' => $testCenter->getUri())),
                'label' => $delivery->getLabel(),
                'text' => __('Monitor')
            );

        }

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
     * Get the agregated data for a filtered set of delivery executions of a given delivery
     * This is performance critical, would need to find a way to optimize to obtain such information
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

        $page = self::paginate($users, $options);

        $mocks = array(
            array(
                'status' => 'idle',
            ),
            array(
                //client will infer possible action based on the current status
                'status' => 'inProgress',
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
                        'elapsed' => 60,
                        'total' => 600
                    )
                )
            ),
            array(
                'status' => 'inProgress',
                'section' => array(
                    'label' => 'section A',
                    'position' => 1,
                    'total' => 3
                ),
                'item' => array(
                    'label' => 'question X',
                    'position' => 5,
                    'total' => 8,
                    'time' => array(
                        'elapsed' => 540,
                        'total' => 600
                    )
                )
            ),
        );

        $testTakers = array();
        foreach($page['data'] as $user) {
            /* @var $user User */
            $firstName = self::getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
            $lastName = self::getUserStringProp($user, PROPERTY_USER_LASTNAME);

            if (empty($firstName) && empty($lastName)) {
                $firstName = self::getUserStringProp($user, RDFS_LABEL);
            }

            $testTakers[] = array(
                'uri' => $user->getIdentifier(),
                'testTaker' => array(
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'companyName' => '',
                ),
                'delivery' => array(
                    'label' => $delivery->getLabel(),
                ),
                'state' => $mocks[array_rand($mocks)],
            );
        }

        $page['data'] = $testTakers;

        return $page;
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
                'company' => '',
                'status' => ''
            );
        }

        $page['data'] = $testTakers;

        return $page;
    }
}
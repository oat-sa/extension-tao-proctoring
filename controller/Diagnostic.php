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

namespace oat\taoProctoring\controller;

use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoProctoring\helpers\BreadcrumbsHelper;
use oat\taoProctoring\helpers\TestCenterHelper;
use oat\taoProctoring\model\implementation\DeliveryService;

require_once __DIR__.'/../../tao/lib/oauth/OAuth.php';

/**
 * Proctoring Diagnostic controller for the readiness check screen
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Diagnostic extends ProctoringModule
{
    /**
     * Display the list of all readiness checks performed on the given test center
     * It also allows launching new ones.
     */
    public function index(){
        $testCenter = $this->getCurrentTestCenter();
        $requestOptions = $this->getRequestOptions();

        $this->setData('title', __('Readiness Check for test site %s', _dh($testCenter->getLabel())));
        $this->composeView(
            'diagnostic-index',
            array(
                'testCenter' => $testCenter->getUri(),
                'set' => TestCenterHelper::getDiagnostics($testCenter, $requestOptions),
                'config' => TestCenterHelper::getDiagnosticConfig($testCenter),
                'installedExtension' => \common_ext_ExtensionsManager::singleton()->isInstalled('ltiDeliveryProvider'),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::diagnostics(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::deliveries($testCenter),
                    )
                )
            )
        );
    }


    public function deliveriesByProctor()
    {
        $deliveryData = array();
        if(\common_ext_ExtensionsManager::singleton()->isInstalled('ltiDeliveryProvider')){
            /** @var DeliveryService $service */
            $service = $this->getServiceManager()->get(DeliveryService::CONFIG_ID);
            $deliveries = $service->getAccessibleDeliveries();


            if(!empty($deliveries)){

                try{
                    $dataStore = new \tao_models_classes_oauth_DataStore();
                    $test_consumer = $dataStore->lookup_consumer('proctoring_key');
                } catch(\tao_models_classes_oauth_Exception $e){
                    $secret = uniqid('proctoring_');
                    \taoLti_models_classes_ConsumerService::singleton()->getRootClass()->createInstanceWithProperties(
                        array(
                            RDFS_LABEL => 'proctoring',
                            PROPERTY_OAUTH_KEY => 'proctoring_key',
                            PROPERTY_OAUTH_SECRET => $secret
                        )
                    );

                    $test_consumer = new \OAuthConsumer('proctoring_key', $secret);
                }
                $session = \common_session_SessionManager::getSession();

                $ltiData = array(
                    'lti_message_type' => 'basic-lti-launch-request',
                    'lti_version' => 'LTI-1p0',

                    'resource_link_id' => rand(0, 9999999),
                    'resource_link_title' => 'Launch Title',
                    'resource_link_label' => 'Launch label',

                    'context_title' => 'Launch Title',
                    'context_label' => 'Launch label',

                    'user_id' => $session->getUserUri(),
                    'roles' => 'Learner',
                    'lis_person_name_full' => $session->getUserLabel(),

                    'tool_consumer_info_product_family_code' => PRODUCT_NAME,
                    'tool_consumer_info_version' => TAO_VERSION,

                    'custom_skip_thankyou' => 'true',
                    'launch_presentation_return_url' => _url('logout', 'Main', 'tao')
                );



                $hmac_method = new \OAuthSignatureMethod_HMAC_SHA1();

                $test_token = new \OAuthToken($test_consumer, '');


                foreach($deliveries as $delivery){
                    $launchUrl =  LTIDeliveryTool::singleton()->getLaunchUrl(array('delivery' => $delivery->getUri()));
                    $acc_req = \OAuthRequest::from_consumer_and_token($test_consumer, $test_token, 'GET', $launchUrl, $ltiData);
                    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

                    $deliveryData[] = array(
                        'id' => $delivery->getUri(),
                        'label' => $delivery->getLabel(),
                        'url' => $acc_req->to_url(),
                        'text' => __('Test')
                    );
                }
            }

        }

        $this->setData('title', __('Available Deliveries'));

        if (\tao_helpers_Request::isAjax()) {
            $this->returnJson(array('list' => $deliveryData));
        } else {
            try{
                $testCenter = $this->getCurrentTestCenter();
                $this->composeView(
                    'diagnostic-deliveries',
                    array('list' => $deliveryData),
                    array(
                        BreadcrumbsHelper::testCenters(),
                        BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                        BreadcrumbsHelper::diagnostics(
                            $testCenter,
                            array(
                                BreadcrumbsHelper::deliveries($testCenter),
                            )
                        ),
                        BreadcrumbsHelper::deliveriesByProctor($testCenter)
                    )
                );
            } catch(\common_Exception $e){
                $this->composeView(
                    'diagnostic-deliveries',
                    array('list' => $deliveryData),
                    array(
                        BreadcrumbsHelper::testCenters(),
                    )
                );
            }
        }
    }
    /**
     * Display the diagnostic runner
     */
    public function diagnostic()
    {
        $testCenter = $this->getCurrentTestCenter();

        $this->setData('title', __('Readiness Check for test site %s', $testCenter->getLabel()));
        $this->composeView(
            'diagnostic-runner',
            array(
                'testCenter' => $testCenter->getUri(),
                'config' => TestCenterHelper::getDiagnosticConfig($testCenter),
            ),
            array(
                BreadcrumbsHelper::testCenters(),
                BreadcrumbsHelper::testCenter($testCenter, TestCenterHelper::getTestCenters()),
                BreadcrumbsHelper::diagnostics(
                    $testCenter,
                    array(
                        BreadcrumbsHelper::deliveries($testCenter),
                    )
                )
            )
        );
    }

    /**
     * Gets the list of diagnostic results
     *
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function diagnosticData()
    {
        try {

            $testCenter = $this->getCurrentTestCenter();
            $requestOptions = $this->getRequestOptions();
            $this->returnJson(TestCenterHelper::getDiagnostics($testCenter, $requestOptions));

        } catch (ServiceNotFoundException $e) {
            \common_Logger::w('No diagnostic service defined for proctoring');
            $this->returnError('Proctoring interface not available');
        }
    }

    /**
     * Removes diagnostic results
     *
     * @throws \common_Exception
     */
    public function remove()
    {
        $testCenter = $this->getCurrentTestCenter();

        $id = $this->getRequestParameter('id');

        $this->returnJson([
            'success' => TestCenterHelper::removeDiagnostic($testCenter, $id)
        ]);
    }
}
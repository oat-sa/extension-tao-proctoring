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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */
namespace oat\taoProctoring\controller;

use common_Logger;
use common_session_SessionManager;
use oat\tao\model\mvc\DefaultUrlService;
use oat\taoDelivery\controller\DeliveryServer as DefaultDeliveryServer;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution as DeliveryExecutionState;
use oat\taoDelivery\model\execution\DeliveryExecution;

/**
 * Override the default DeliveryServer Controller
 *
 * @package taoProctoring
 */
class DeliveryServer extends DefaultDeliveryServer
{

    /**
     * constructor: initialize the service and the default data
     * @return DeliveryServer
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Overrides the content extension data
     * @see {@link \taoDelivery_actions_DeliveryServer}
     */
    public function index()
    {
        // if the test taker passes by this page, he/she cannot access to any delivery without proctor authorization,
        // whatever the delivery execution status is.
        $user = common_session_SessionManager::getSession()->getUser();
        $startedExecutions = $this->service->getResumableDeliveries($user);
        foreach($startedExecutions as $startedExecution) {
            if($startedExecution->getDelivery()->exists()) {
                $this->revoke($startedExecution);
            }
        }

        parent::index();
    }

    /**
     * Overrides the return URL
     * @return string the URL
     */
    protected function getReturnUrl()
    {
        return $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringDeliveryServer');
    }

    /**
     * The awaiting authorization screen
     */
    public function awaitingAuthorization()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $executionState = $deliveryExecution->getState()->getUri();

        $runDeliveryUrl = _url('runDeliveryExecution', null, null, array('deliveryExecution' => $deliveryExecution->getIdentifier()));

        // if the test is in progress, first pause it to avoid inconsistent storage state
        if (DeliveryExecutionState::STATE_ACTIVE == $executionState) {
            $deliveryExecutionStateService->pauseExecution($deliveryExecution, [
                'reasons' => ['category' => 'System'],
                'comment' => __('System generated pause.'),
            ]);
        }

        // we need to change the state of the delivery execution
        if (!in_array($executionState , array(DeliveryExecutionState::STATE_FINISHED, DeliveryExecutionState::STATE_TERMINATED))) {
            if (DeliveryExecutionState::STATE_AUTHORIZED !== $executionState) {
                $deliveryExecutionStateService->waitExecution($deliveryExecution);
            }

            $this->setData('deliveryExecution', $deliveryExecution->getIdentifier());
            $this->setData('deliveryLabel', addslashes($deliveryExecution->getLabel()));
            $this->setData('returnUrl', $this->getReturnUrl());
            $this->setData('cancelUrl', _url('cancelExecution', 'DeliveryServer', 'taoProctoring', ['deliveryExecution' => $deliveryExecution->getIdentifier()]));
            $this->setData('cancelable', $deliveryExecutionStateService->isCancelable($deliveryExecution));
            $this->setData('userLabel', common_session_SessionManager::getSession()->getUserLabel());
            $this->setData('client_config_url', $this->getClientConfigUrl());
            $this->setData('showControls', true);
            $this->setData('runDeliveryUrl', $runDeliveryUrl);

            //set template
            $this->setData('homeUrl', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringHome'));
            $this->setData('logout', $this->getServiceManager()->get(DefaultUrlService::SERVICE_ID)->getUrl('ProctoringLogout'));
            $this->setData('content-template', 'DeliveryServer/awaiting.tpl');
            $this->setData('content-extension', 'taoProctoring');
            $this->setView('DeliveryServer/layout.tpl', 'taoDelivery');
        } else {
            // inconsistent state
            common_Logger::i(get_called_class() . '::awaitingAuthorization(): cannot wait authorization for delivery execution ' . $deliveryExecution->getIdentifier() . ' with state ' . $executionState);
            return $this->redirect($this->getReturnUrl());
        }
    }

    /**
     * The action called to check if the requested delivery execution has been authorized by the proctor
     */
    public function isAuthorized()
    {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        $executionState = $deliveryExecution->getState()->getUri();

        $authorized = false;
        $success = true;
        $message = null;

        // reacts to a few particular states
        switch ($executionState) {
            case DeliveryExecutionState::STATE_AUTHORIZED:
                    $authorized = true;
                break;

            case DeliveryExecutionState::STATE_TERMINATED:
            case DeliveryExecutionState::STATE_FINISHED:
                $success = false;
                $message = __('The assessment has been terminated. You cannot interact with it anymore.');
                break;

            case DeliveryExecutionState::STATE_PAUSED:
                $success = false;
                $message = __('The assessment has been suspended. To resume your assessment, please relaunch it and contact your proctor if required.');
                break;
        }

        $this->returnJson(array(
            'authorized' => $authorized,
            'success' => $success,
            'message' => $message
        ));
    }

    /**
     * Cancel delivery authorization request.
     */
    public function cancelExecution()
    {

        $deliveryExecution = $this->getCurrentDeliveryExecution();
        /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
        $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
        $reason = [
            'reasons' => ['category' => 'Examinee', 'subCategory' => 'Navigation'],
        ];
        if ($deliveryExecution->getState()->getUri() === DeliveryExecutionState::STATE_AUTHORIZED) {
            $reason['comment'] = __('Automatically reset by the system due to the test taker choosing not to proceed with the authorized test.');
        } else {
            $reason['comment'] = __('Automatically reset by the system due to authorization request being cancelled by test taker.');
        }
        $deliveryExecutionStateService->cancelExecution(
            $deliveryExecution,
            $reason
        );
        return $this->redirect($this->getReturnUrl());
    }

    protected function revoke(DeliveryExecution $deliveryExecution)
    {
        if($deliveryExecution->getState()->getUri() != DeliveryExecutionState::STATE_PAUSED){
            /** @var DeliveryExecutionStateService $deliveryExecutionStateService */
            $deliveryExecutionStateService = $this->getServiceManager()->get(DeliveryExecutionStateService::SERVICE_ID);
            $deliveryExecutionStateService->pauseExecution(
                $deliveryExecution,
                [
                    'reasons' => ['category' => 'focus-loss'],
                    'comment' => __('Assessment has been paused due to attempt to switch to another window/tab.'),
                ]
            );
        }
    }
}

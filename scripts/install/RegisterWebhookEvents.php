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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoProctoring\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\webhooks\WebhookEventsServiceInterface;
use oat\taoProctoring\model\event\DeliveryExecutionFinished;

class RegisterWebhookEvents extends InstallAction
{
    /**
     * @param $params
     * @throws \common_Exception
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $this->registerEvents([
            DeliveryExecutionFinished::EVENT_NAME
        ]);
    }

    /**
     * @param string[] $eventNames
     * @throws \common_Exception
     * @throws InvalidServiceManagerException
     */
    protected function registerEvents(array $eventNames) {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        /** @var WebhookEventsServiceInterface $webhooksService */
        $webhooksService = $this->getServiceLocator()->get(WebhookEventsServiceInterface::SERVICE_ID);

        foreach ($eventNames as $eventName) {
            $webhooksService->registerEvent($eventName);
        }

        /** @noinspection PhpParamsInspection */
        $this->getServiceManager()->register(WebhookEventsServiceInterface::SERVICE_ID, $webhooksService);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}

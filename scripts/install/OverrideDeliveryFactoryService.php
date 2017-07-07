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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 * @author Aleksej Tikhanovich <aleksej@taotesting.com>
 */
namespace oat\taoProctoring\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoProctoring\model\ProctorService;

class OverrideDeliveryFactoryService extends InstallAction
{
    public function __invoke($params)
    {

        $proctorService = $this->getServiceManager()->get(ProctorService::SERVICE_ID);
        $config = $proctorService->getOptions();
        $config[ProctorService::PROCTORED_BY_DEFAULT] = false;

        $service = new ProctorService($config);
        $service->setServiceManager($this->getServiceManager());
        $this->getServiceManager()->register(ProctorService::SERVICE_ID, $service);

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(DeliveryCreatedEvent::class, [ProctorService::SERVICE_ID, 'listenCreateDeliveryEvent']);
        $eventManager->attach(DeliveryUpdatedEvent::class, [ProctorService::SERVICE_ID, 'listenUpdateDeliveryEvent']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

    }
}

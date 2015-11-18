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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\implementation;

use oat\taoDelivery\models\classes\execution\DeliveryExecution;

/**
 * Manage the flagged items
 */
class ExtendedStateService
{
    const STORAGE_PREFIX = 'extra_';
    
    private $cache = null;

    /**
     * Retrieves extended state information
     * @param DeliveryExecution $deliveryExecution
     * @return mixed
     * @throws \common_exception_InconsistentData
     */
    protected function getExtra(DeliveryExecution $deliveryExecution)
    {
        $testSessionId = $deliveryExecution->getIdentifier();
        $userUri = $deliveryExecution->getUserIdentifier();

        if (!isset($this->cache[$testSessionId])) {
            $storageService = \tao_models_classes_service_StateStorage::singleton();
            $data = $storageService->get($userUri, self::STORAGE_PREFIX . $testSessionId);

            if ($data) {
                $data = json_decode($data, true);

                if (is_null($data)) {
                    throw new \common_exception_InconsistentData('Unable to decode extra for test session ' . $testSessionId);
                }
            } else {
                $data = array();
            }
            $this->cache[$testSessionId] = $data;
        }

        return $this->cache[$testSessionId];
    }
    
    /**
     * Stores extended state information
     * @param DeliveryExecution $deliveryExecution
     * @param mixed $extra
     */
    protected function saveExtra(DeliveryExecution $deliveryExecution, $extra)
    {
        $testSessionId = $deliveryExecution->getIdentifier();
        $userUri = $deliveryExecution->getUserIdentifier();

        $this->cache[$testSessionId] = $extra;

        $storageService = \tao_models_classes_service_StateStorage::singleton();
        $storageService->set($userUri, self::STORAGE_PREFIX . $testSessionId, json_encode($extra));
    }

    /**
     * Sets an extra value
     * @param DeliveryExecution $deliveryExecution
     * @param string $name
     * @param mixed $value
     * @throws \common_exception_InconsistentData
     */
    public function setValue(DeliveryExecution $deliveryExecution, $name, $value) {
        $extra = $this->getExtra($deliveryExecution);
        $extra[$name] = $value;
        $this->saveExtra($deliveryExecution, $extra);
    }

    /**
     * Gets an extra value
     * @param DeliveryExecution $deliveryExecution
     * @param string $name
     * @return mixed|null
     * @throws \common_exception_InconsistentData
     */
    public function getValue(DeliveryExecution $deliveryExecution, $name) {
        $extra = $this->getExtra($deliveryExecution);
        return isset($extra[$name]) ? $extra[$name] : null;
    }
}
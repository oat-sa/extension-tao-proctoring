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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoProctoring\model\delivery;

use oat\tao\model\plugins\PluginModule;
use oat\taoDelivery\model\DeliveryPluginService as BaseDeliveryPluginService;
use oat\taoProctoring\model\ProctorService;

class DeliveryPluginService extends BaseDeliveryPluginService
{

    public function checkPlugin(PluginModule $plugin, \core_kernel_classes_Resource $delivery )
    {
        if ($this->isProctoredDelivery($delivery)) {
            return in_array('taoProctoring', $plugin->getTags());
        } else {
            return in_array('taoDelivery', $plugin->getTags());
        }
    }

    /**
     * Check whether secure plugins must be used.
     * @param \core_kernel_classes_Resource $delivery
     * @return bool
     */
    private function isProctoredDelivery(\core_kernel_classes_Resource $delivery)
    {
        $hasProctor = $delivery->getOnePropertyValue($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR));
        $result = $hasProctor instanceof \core_kernel_classes_Resource &&
            $hasProctor->getUri() == ProctorService::ACCESSIBLE_PROCTOR_ENABLED;
        return $result;
    }
}

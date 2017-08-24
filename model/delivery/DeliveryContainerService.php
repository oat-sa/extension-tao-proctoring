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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoProctoring\model\delivery;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\plugins\PluginModule;
use oat\taoDeliveryRdf\model\DeliveryContainerService as DeliveryRdfContainerService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoProctoring\model\ProctorService;

/**
 * Override the DeliveryContainerService in order to filter the plugin list based on the security flag.
 *
 * @author Tikhanovich Aleksej <aleksej@taotesting.com>
 */
class DeliveryContainerService extends DeliveryRdfContainerService
{

    use OntologyAwareTrait;

    /**
     * Get the execution plugins, and filter the plugins that belongs to the security category
     * if the execution has been configured accordingly.
     *
     * @param DeliveryExecution $deliveryExecution
     * @return array the list of plugins
     */
    public function getPlugins(DeliveryExecution $deliveryExecution)
    {
        $plugins = parent::getPlugins($deliveryExecution);
        $delivery = $deliveryExecution->getDelivery();

        if ($this->isSecureDelivery($delivery)) {
            if ($this->isProctoredDelivery($delivery)) {
                return array_filter($plugins, function(PluginModule $plugin) {
                    return !in_array('only_warning', $plugin->getTags());
                });
            } else {
                return array_filter($plugins, function(PluginModule $plugin) {
                    return !in_array('proctor', $plugin->getTags());
                });
            }
        }

        //otherwise filter the security plugins
        return array_filter($plugins, function(PluginModule $plugin) {
            return $plugin->getCategory() != 'security';
        });
    }

    /**
     * Check whether secure plugins must be used.
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     * @throws \taoLti_models_classes_LtiException
     */
    private function isSecureDelivery(\core_kernel_classes_Resource $delivery)
    {
        $hasSecurityPlugins = $delivery->getOnePropertyValue($this->getProperty(self::DELIVERY_SECURITY_PLUGINS_PROPERTY));
        $result = $hasSecurityPlugins instanceof core_kernel_classes_Resource &&
                  $hasSecurityPlugins->getUri() == self::CHECK_MODE_ENABLED;

        return $result;
    }

    /**
     * Check whether secure plugins must be used.
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     * @throws \taoLti_models_classes_LtiException
     */
    private function isProctoredDelivery(\core_kernel_classes_Resource $delivery)
    {
        $hasProctor = $delivery->getOnePropertyValue($this->getProperty(ProctorService::ACCESSIBLE_PROCTOR));
        $result = $hasProctor instanceof core_kernel_classes_Resource &&
            $hasProctor->getUri() == ProctorService::ACCESSIBLE_PROCTOR_ENABLED;

        return $result;
    }

}

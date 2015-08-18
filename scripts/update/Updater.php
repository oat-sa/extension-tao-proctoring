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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\scripts\update;

use \common_ext_ExtensionUpdater;
use \common_ext_ExtensionsManager;
use \tao_install_ExtensionInstaller;
use oat\tao\model\ThemeRegistry;
use oat\tao\model\websource\DirectWebSource;
use oat\taoProctoring\model\implementation\DeliveryService;
/**
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends common_ext_ExtensionUpdater {

    /**
     * @param string $initialVersion
     * @return string string
     */
    public function update($initialVersion) {
        
        $currentVersion = $initialVersion;
        $ext = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoProctoring');
        
        if ($currentVersion == '0.1') {
            $service = new DeliveryService();
            $ext->setConfig('delivery', $service);
            $currentVersion = '0.2';
        }

        return $currentVersion;
    }

}
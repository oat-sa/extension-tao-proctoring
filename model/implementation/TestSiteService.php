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
 *
 */
namespace oat\taoProctoring\model\implementation;

use oat\oatbox\service\ConfigurableService;

/**
 * Sample TestSite Service for proctoring
 * 
 */
class TestSiteService extends ConfigurableService
{

    /**
     * Gets a list of available test sites
     *
     * @return array
     * @throws ServiceNotFoundException
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function getTestSites() {

        $testSites = array();

        $n = 3;
        for($i = 0; $i < $n; $i ++) {
            $id = $i + 1;
            $testSites[] = array(
                'id' => $id,
                'url' => _url('testSite', 'TaoProctoring', null, array('id' => $id)),
                'label' => __('Test site %d', $id),
                'text' => __('Manage'),
            );
        }

        return $testSites;
    }

    
}

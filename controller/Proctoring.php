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

namespace oat\taoProctoring\controller;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Proctoring extends \tao_actions_CommonModule
{
    protected $currentTestCenter = null;
    protected $currentDelivery   = null;

    protected function getCurrentTestCenter()
    {
        if (is_null($this->currentTestCenter)) {
            $testCenterUri           = $this->getRequestParameter('testCenter');
            $this->currentTestCenter = new core_kernel_classes_Resource($testCenterUri);
        }
        return $this->currentTestCenter;
    }

    protected function getCurrentDelivery()
    {
        if (is_null($this->currentDelivery)) {
            $deliveryUri           = $this->getRequestParameter('delivery');
            $this->currentDelivery = new core_kernel_classes_Resource($deliveryUri);
        }
        return $this->currentDelivery;
    }

    protected function composeView($template, $breadcrumbs = array())
    {
        $this->defaultData();
        $this->setData('userLabel', SessionManager::getSession()->getUserLabel());
        $this->setData('clientConfigUrl', $this->getClientConfigUrl());
        $this->setData('template', $template);
        $this->setData('breadcrumbs', $breadcrumbs);
        $this->setView('layout.tpl');
    }
}
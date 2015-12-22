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
namespace oat\taoProctoring\model;

use oat\oatbox\user\User;
use oat\taoTestTaker\models\TestTakerService;
use core_kernel_classes_Resource as Resource;
use core_kernel_classes_Class;
use core_kernel_classes_Property as Property;
use tao_models_classes_ClassService;
use taoDelivery_models_classes_DeliveryRdf;

/**
 * Service to manage eligible deliveries
 */
class IneligibileException extends \common_Exception
{
        
}

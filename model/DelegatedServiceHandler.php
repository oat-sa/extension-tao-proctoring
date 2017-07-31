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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoProctoring\model;


use oat\oatbox\PhpSerializable;
use oat\oatbox\user\User;

interface DelegatedServiceHandler extends PhpSerializable
{
    /**
     * By default used only one Delegated Service
     * But when delegated Service extended and has many implementations
     * then delegated Service will determine which Service should be used in the current context
     * @param $user
     * @param $deliveryId
     * @return bool
     */
    public function isSuitable(User $user, $deliveryId = null);
}

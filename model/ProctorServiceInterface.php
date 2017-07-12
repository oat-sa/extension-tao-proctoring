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


use oat\oatbox\user\User;

interface ProctorServiceInterface
{
    const SERVICE_ID = 'taoProctoring/ProctorAccess';

    /**
     * Gets all deliveries available for a proctor
     * @param User $proctor
     * @param $context
     * @return array
     */
    public function getProctorableDeliveries(User $proctor, $context = null);

    /**
     * @param User $proctor
     * @param null $delivery
     * @param null $context
     * @param array $options
     * @return mixed
     */
    public function getProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = []);

    /**
     * @param User $proctor
     * @param null $delivery
     * @param null $context
     * @param array $options
     * @return mixed
     */
    public function countProctorableDeliveryExecutions(User $proctor, $delivery = null, $context = null, $options = []);
}

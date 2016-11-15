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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoProctoring\model\textConverter;

use oat\tao\model\textConverter\TextConverterService;

class ProctoringTextConverter extends TextConverterService
{
    const SERVICE_ID = 'taoProctoring/textConverter';

    /**
     * Return the translation of key
     *
     * @return array
     */
    public function getTextRegistry()
    {
        return array(
            'Assign administrator' => __('Assign administrator'),
            'Assign proctors' => __('Assign proctors'),
            'Please select one or more test site to manage proctors' => __('Please select one or more test site to manage proctors'),
            'Create Proctor' => __('Create Proctor'),
            'Create and authorize a proctor to the selected test sites' => __('Create and authorize a proctor to the selected test sites'),
            'Manage Proctors' => __('Manage Proctors'),
            'Define sub-centers' => __('Define sub-centers'),
            'The proctors will be authorized. Continue ?' => __('The proctors will be authorized. Continue ?'),
            'The proctors will be revoked. Continue ?' => __('The proctors will be revoked. Continue ?'),
            'The proctor will be authorized. Continue ?' => __('The proctor will be authorized. Continue ?'),
            'The proctor will be revoked. Continue ?' => __('The proctor will be revoked. Continue ?'),
            'Authorized proctors' => __('Authorized proctors'),
            'Partially authorized proctors' => __('Partially authorized proctors'),
            'No assigned proctors' => __('No assigned proctors'),
            'Assigned proctors' => __('Assigned proctors'),
            'Creates and authorizes proctor' => __('Creates and authorizes proctor'),
            'Authorize the selected proctors' => __('Authorize the selected proctors'),
            'Authorize the proctor' => __('Authorize the proctor'),
            'Revoke authorization for the selected proctors' => __('Revoke authorization for the selected proctor'),
            'Revoke the proctor' => __('Revoke the proctor'),
            'Proctors authorized' => __('Proctors authorized'),
            'Proctors revoked' => __('Proctors revoked'),
            'Proctor created' => __('Proctor created'),
            'No proctors in request param' => __('No proctors in request param'),
            'Test site %s' =>__('Test site %s'),
            'Test center saved' => __('Test center saved'),
            'Edit test center' => __('Edit test center')
        );
    }


}
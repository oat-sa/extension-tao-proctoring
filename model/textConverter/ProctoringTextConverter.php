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

use oat\tao\model\textConverter\NullTextConverter;

class ProctoringTextConverter extends NullTextConverter
{
    const SERVICE_ID = 'taoProctoring/textConverter';

    public function getTextRegistry()
    {
        return [
            'Assign administrator' => __('Proctors'),
            'Assign proctors' => __('Instructors'),
            'Please select one or more test site to manage proctors' => __('Please select one or more test site to manage instructors'),
            'Create Proctor' => __('Create Instructor'),
            'Create and authorize a proctor to the selected test sites' => __('Create and authorize an instructor to the selected test sites'),
            'Manage Proctors' => __('Manage Instructors'),
            'Define sub-centers' => __('Define class rooms'),
            'The proctors will be authorized. Continue ?' => __('The instructors will be authorized. Continue ?'),
            'The proctors will be revoked. Continue ?' => __('The instructors will be revoked. Continue ?'),
            'The proctor will be authorized. Continue ?' => __('The instructor will be authorized. Continue ?'),
            'The proctor will be revoked. Continue ?' => __('The instructor will be revoked. Continue ?'),
            'Authorized proctors' => __('Authorized instructors'),
            'Partially authorized proctors' => __('Partially authorized instructors'),
            'No assigned proctors' => __('No assigned instructors'),
            'Assigned proctors' => __('Assigned instructors'),
            'Creates and authorizes proctor' => __('Creates and authorizes instructor'),
            'Authorize the selected proctors' => __('Authorize the selected instructors'),
            'Authorize the proctor' => __('Authorize the instructor'),
            'Revoke authorization for the selected proctors' => __('Revoke authorization for the selected instructors'),
            'Revoke the proctor' => __('Revoke the instructor'),
            'Proctors authorized' => __('Instructors authorized'),
            'Proctors revoked' => __('Instructors revoked'),
            'Proctor created' => __('Instructor created')
        ];
    }

}
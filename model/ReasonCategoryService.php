<?php
/*
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\model;

use oat\oatbox\service\ConfigurableService;

/**
 * Provides methods to return the list of available categories and subcategories
 */
class ReasonCategoryService extends ConfigurableService
{
    const SERVICE_ID = 'taoProctoring/reasonCategory';

    /**
     * @return array
     */
    private function getDefinitions()
    {
        return [
            array(
                'id' => 'category',
                'placeholder' => __('Issue Category')
            ),
            array(
                'id' => 'subCategory',
                'placeholder' => __('Subcategory')
            )
        ];
    }

    /**
     * Default category list. It can be overwritten by child classes.
     * Pay attention to the keys!
     *
     * @return array
     */
    protected function getCategories()
    {
        return array(
            array(
                'id' => 'environment',
                'label' => __('Environment'),
                'categories' => array(
                    array(
                        'id' => 'comfort',
                        'label' => __('Comfort')
                    ),
                    array(
                        'id' => 'disturbance',
                        'label' => __('Disturbance')
                    ),
                    array(
                        'id' => 'noise',
                        'label' => __('Noise')
                    ),
                    array(
                        'id' => 'powerOutage',
                        'label' => __('Power Outage')
                    ),
                    array(
                        'id' => 'weather',
                        'label' => __('Weather')
                    ),
                )
            ),
            array(
                'id' => 'examinee',
                'label' => __('Examinee'),
                'categories' => array(
                    array(
                        'id' => 'behaviour',
                        'label' => __('Behaviour')
                    ),
                    array(
                        'id' => 'complaint',
                        'label' => __('Complaint')
                    ),
                    array(
                        'id' => 'idAuthorization',
                        'label' => __('ID/Authorization')
                    ),
                    array(
                        'id' => 'illness',
                        'label' => __('Illness')
                    ),
                    array(
                        'id' => 'late',
                        'label' => __('Late')
                    ),
                    array(
                        'id' => 'navigation',
                        'label' => __('Navigation')
                    ),
                    array(
                        'id' => 'noShow',
                        'label' => __('No Show')
                    ),
                )
            ),
            array(
                'id' => 'proctorStaff',
                'label' => __('Proctor/Staff'),
                'categories' => array(
                    array(
                        'id' => 'behaviour',
                        'label' => __('Behaviour')
                    ),
                    array(
                        'id' => 'compliance',
                        'label' => __('Compliance')
                    ),
                    array(
                        'id' => 'error',
                        'label' => __('Error')
                    ),
                    array(
                        'id' => 'late',
                        'label' => __('Late')
                    ),
                    array(
                        'id' => 'noShow',
                        'label' => __('No Show')
                    ),
                )
            ),
            array(
                'id' => 'technical',
                'label' => __('Technical'),
                'categories' => array(
                    array(
                        'id' => 'freezing',
                        'label' => __('Freezing')
                    ),
                    array(
                        'id' => 'launching',
                        'label' => __('Launching')
                    ),
                    array(
                        'id' => 'network',
                        'label' => __('Network')
                    ),
                    array(
                        'id' => 'printing',
                        'label' => __('Printing')
                    ),
                    array(
                        'id' => 'testingWorkstation',
                        'label' => __('Testing Workstation')
                    ),
                )
            ),
            array(
                'id' => 'other',
                'label' => __('Other')
            )
        );
    }

    /**
     * @return array
     */
    public function getIrregularities()
    {
        return [
            'categoriesDefinitions' => $this->getDefinitions(),
            'categories' => $this->getCategories()
        ];
    }
}
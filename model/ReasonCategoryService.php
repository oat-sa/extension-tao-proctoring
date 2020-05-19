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
class ReasonCategoryService extends ConfigurableService implements ReasonCategoryServiceInterface
{
    /**
     * @return array
     */
    private function getDefinitions()
    {
        return [
            ['id' => self::PROPERTY_CATEGORY,       'placeholder' => __('Issue Category')],
            ['id' => self::PROPERTY_SUBCATEGORY,    'placeholder' => __('Subcategory')],
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
        return [
            [
                'id' => 'environment',
                'label' => __('Environment'),
                'categories' => [
                    ['id' => 'comfort',     'label' => __('Comfort')],
                    ['id' => 'disturbance', 'label' => __('Disturbance')],
                    ['id' => 'noise',       'label' => __('Noise')],
                    ['id' => 'powerOutage', 'label' => __('Power Outage')],
                    ['id' => 'weather',     'label' => __('Weather')],
                ],
            ],
            [
                'id' => 'examinee',
                'label' => __('Examinee'),
                'categories' => [
                    ['id' => 'behaviour',   'label' => __('Behaviour')],
                    ['id' => 'complaint',   'label' => __('Complaint')],
                    ['id' => 'idAuthorization', 'label' => __('ID/Authorization')],
                    ['id' => 'illness',     'label' => __('Illness')],
                    ['id' => 'late',        'label' => __('Late')],
                    ['id' => 'navigation',  'label' => __('Navigation')],
                    ['id' => 'noShow',      'label' => __('No Show')],
                ],
            ],
            [
                'id' => 'proctorStaff',
                'label' => __('Proctor/Staff'),
                'categories' => [
                    ['id' => 'behaviour',   'label' => __('Behaviour')],
                    ['id' => 'compliance',  'label' => __('Compliance')],
                    ['id' => 'error',       'label' => __('Error')],
                    ['id' => 'late',        'label' => __('Late')],
                    ['id' => 'noShow',      'label' => __('No Show')],
                ]
            ],
            [
                'id' => 'technical',
                'label' => __('Technical'),
                'categories' => [
                    ['id' => 'freezing',    'label' => __('Freezing')],
                    ['id' => 'launching',   'label' => __('Launching')],
                    ['id' => 'network',     'label' => __('Network')],
                    ['id' => 'printing',    'label' => __('Printing'),],
                    ['id' => 'testingWorkstation', 'label' => __('Testing Workstation')]
                ]
            ],
            ['id' => 'other', 'label' => __('Other')]
        ];
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

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
namespace oat\taoProctoring\controller\form;

use oat\taoProctoring\model\textConverter\ProctoringTextConverterTrait;
use \tao_actions_form_Users;
use \tao_helpers_form_FormFactory;

class AddProctor extends tao_actions_form_Users
{
    use ProctoringTextConverterTrait;

    public function __construct()
    {
        parent::__construct(new \core_kernel_classes_Class(CLASS_TAO_USER), null, true);
    }

    protected function initForm()
    {
        parent::initForm();
        $this->form->setActions($this->getCustomActions(), 'bottom');
    }

    protected function getCustomActions()
    {
        $returnValue = array();
        
        $actions = tao_helpers_form_FormFactory::getElement('save', 'Free');
        $value   = '';

        $buttonSave = tao_helpers_form_FormFactory::getElement('Save', 'Button');
        $buttonSave->setIcon('icon-add');
        $buttonSave->setValue($this->convert('Create Proctor'));
        $buttonSave->setType('submit');
        $buttonSave->addClass('form-submitter btn-success small');
        $value .= $buttonSave->render();

        $buttonCancel = tao_helpers_form_FormFactory::getElement('Cancel', 'Button');
        $buttonCancel->setIcon('icon-undo');
        $buttonCancel->setValue(__('Cancel'));
        $buttonCancel->setType('cancel');
        $buttonCancel->addClass('form-submitter btn-diasble small');
        $value .= $buttonCancel->render();

        $actions->setValue($value);
        $returnValue[] = $actions;

        return $returnValue;
    }

    protected function initElements()
    {
        $title = tao_helpers_form_FormFactory::getElement('title', 'Free');
        $title->setValue('<h2>' . $this->convert('Create and authorize a proctor to the selected test sites') . '</h2>');
        $this->form->addElement($title);
        parent::initElements();
        $this->form->removeElement(\tao_helpers_Uri::encode(PROPERTY_USER_ROLES));
    }
}
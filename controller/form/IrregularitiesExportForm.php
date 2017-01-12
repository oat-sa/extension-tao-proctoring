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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\controller\form;

/**
 *
 * @access public
 * @author Antoine Robin, <antoine.robin@vesperiagroup.com>
 */
class IrregularitiesExportForm extends \tao_helpers_form_FormContainer
{
    private $instanceUri;

    public function __construct($instanceUri)
    {
        $this->instanceUri = $instanceUri;
        parent::__construct();

    }

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('export-form');
        $submitElt = \tao_helpers_form_FormFactory::getElement('export', 'Free');
        $submitElt->setValue('<a href="#" class="form-submitter btn-success small"><span class="icon-export"></span> ' . __('Export') . '</a>');

        $this->form->setActions(array($submitElt), 'bottom');
        $this->form->setActions(array(), 'top');

    }

    /**
     * Used to create the form elements and bind them to the form instance
     *
     * @access protected
     * @return mixed
     */
    protected function initElements()
    {
        //create date picker elements
        $fromDateElt = \tao_helpers_form_FormFactory::getElement('from', 'calendar');
        $fromDateElt->setDescription(__('Start date of the export'));


        $toDateElt = \tao_helpers_form_FormFactory::getElement('to', 'calendar');
        $toDateElt->setDescription(__('End date of the export'));
        $toDateElt->setValue(time());

        $this->form->addElement($fromDateElt);
        $this->form->addElement($toDateElt);

        if (!is_null($this->instanceUri)) {
            $instanceElt = \tao_helpers_form_FormFactory::getElement('uri', 'Hidden');
            $instanceElt->setValue($this->instanceUri);
            $this->form->addElement($instanceElt);
        }

    }
}

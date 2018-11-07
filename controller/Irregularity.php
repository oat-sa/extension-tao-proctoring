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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoProctoring\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoProctoring\controller\form\IrregularitiesExportForm;
use oat\taoProctoring\model\service\IrregularityReport;

/**
 * Irregularity controller
 *
 * @author  Open Assessment Technologies SA
 * @package taoProctoring
 * @license GPL-2.0
 *
 */
class Irregularity extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;
    use OntologyAwareTrait;

    /**
     * Displays the form to export irregularities and handles it after submit
     */
    public function index()
    {
        $formContainer = new IrregularitiesExportForm($this->getRequestParameter('uri'));
        $myForm = $formContainer->getForm();

        if ($myForm->isValid() && $myForm->isSubmited()) {
            $delivery = $this->getResource(\tao_helpers_Uri::decode($this->getRequestParameter('uri')));

            $from = $this->hasRequestParameter('from')
                ? strtotime($this->getRequestParameter('from'))
                : '';

            $to = $this->hasRequestParameter('to')
                ? strtotime($this->getRequestParameter('to'))
                : '';

            /** @var $IrregularityReport IrregularityReport */
            $IrregularityReport = $this->getServiceLocator()->get(IrregularityReport::SERVICE_ID);

            return $this->returnTaskJson($IrregularityReport->getIrregularities($delivery, $from, $to));
        } else {
            $this->setData('myForm', $myForm->render());
            $this->setView('Irregularities/index.tpl');
        }
    }
}

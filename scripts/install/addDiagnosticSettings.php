<?php

namespace oat\taoProctoring\scripts\install;

use oat\taoClientDiagnostic\model\authorization\Anonymous;
use oat\taoClientDiagnostic\model\authorization\Authorization;
use oat\taoClientDiagnostic\model\storage\Sql;
use oat\taoClientDiagnostic\model\storage\Storage;

class addDiagnosticSettings extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        //Set diagnostic config
        $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoClientDiagnostic');
        $config = $extension->getConfig('clientDiag');
        $extension->setConfig('clientDiag', array_merge_recursive($config, array(
            'performances' => array(
                'samples' => array(
                    'taoClientDiagnostic/tools/performances/data/sample1/',
                    'taoClientDiagnostic/tools/performances/data/sample2/',
                    'taoClientDiagnostic/tools/performances/data/sample3/'
                ),
                'occurrences' => 10,
                'timeout' => 30,
                'optimal' => 0.05,
                'threshold' => 0.75
            ),
            'bandwidth' => array(
                'unit' => 0.16,
                'ideal' => 45,
                'max' => 100,
            ),
        )));

        //Set diagnostic authorization
        $authService = new Anonymous();
        $authService->setServiceManager($this->getServiceManager());
        $this->getServiceManager()->register(Authorization::SERVICE_ID, $authService);

        //Set diagnostic storage
        $storageService = new Sql(array(
            'persistence' => 'default'
        ));
        $storageService->setServiceManager($this->getServiceManager());
        $this->getServiceManager()->register(Storage::SERVICE_ID, $storageService);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Diagnostic settings added to Proctoring extension');
    }
}

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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoProctoring\model\import;

use oat\generis\model\user\UserRdf;
use oat\tao\model\user\import\RdsUserImportService;
use oat\taoProctoring\model\ProctorService;

/**
 * Class ProctorCsvImporter
 *
 * Implementation of RdsUserImportService to import proctor resource from a CSV
 *
`
$userImporter = $this->getServiceLocator()->get(UserCsvImporterFactory::SERVICE_ID);
$importer = $userImporter->getImporter(CsvProctorImporter::USER_IMPORTER_TYPE);
$report = $importer->import($filePath);
`
 *
 * or by command line:
`
sudo -u www-data php index.php 'oat\tao\scripts\tools\import\ImportUsersCsv' -t proctor -f tao/test/user/import/example.csv
`
 *
 */
class ProctorCsvImporter extends RdsUserImportService
{
    CONST USER_IMPORTER_TYPE = 'proctor';

    /**
     * Add test taker role to user to import
     *
     * @param $filePath
     * @param array $extraProperties
     * @param array $options
     * @return \common_report_Report
     * @throws \Exception
     * @throws \common_exception_Error
     */
    public function import($filePath, $extraProperties = [], $options = [])
    {
        $extraProperties[UserRdf::PROPERTY_ROLES] = ProctorService::ROLE_PROCTOR;
        $extraProperties['roles'] = ProctorService::ROLE_PROCTOR;
        return parent::import($filePath, $extraProperties, $options);
    }
}
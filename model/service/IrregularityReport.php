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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoProctoring\model\service;

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoOutcomeUi\model\ResultsService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\tao\helpers\UserHelper;
use oat\oatbox\user\User;

class IrregularityReport extends AbstractIrregularityReport
{
    private $userNames = [];

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @param string                        $from
     * @param string                        $to
     * @return array
     */
    public function getIrregularitiesTable(\core_kernel_classes_Resource $delivery, $from = '', $to = '')
    {
        $export = [
            [__('date'), __('author'), __('test taker'), __('category'), __('subcategory'), __('comment')],
        ];
        $deliveryLog = ServiceManager::getServiceManager()->get(DeliveryLog::SERVICE_ID);
        $service = ResultsService::singleton();
        $implementation = $service->getReadableImplementation($delivery);

        $service->setImplementation($implementation);

        $results = $service->getImplementation()->getResultByDelivery([$delivery->getUri()]);

        foreach ($results as $res) {
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($res['deliveryResultIdentifier']);
            $logs = $deliveryLog->get(
                $deliveryExecution->getIdentifier(),
                'TEST_IRREGULARITY'
            );
            foreach ($logs as $data) {
                $exportable = [];
                if ((empty($from) || $data['created_at'] > $from) && (empty($to) || $data['created_at'] < $to)) {
                    $exportable[] = \tao_helpers_Date::displayeDate($data['created_at']);
                    $exportable[] = $this->getUserName($data['created_by']);
                    $exportable[] = $this->getUserName($res['testTakerIdentifier']);
                    $exportable[] = $data['data']['reason']['reasons']['category'];
                    $exportable[] = (isset($data['data']['reason']['reasons']['subCategory'])) ? $data['data']['reason']['reasons']['subCategory'] : '';
                    $exportable[] = $data['data']['reason']['comment'];
                    $export[] = $exportable;
                }
            }
        }

        return $export;
    }

    /**
     * @param string $userId
     * @return string
     */
    private function getUserName($userId)
    {
        if (!isset($this->userNames[$userId])) {
            $user = UserHelper::getUser($userId);
            $userName = UserHelper::getUserName($user, true);
            if (empty($userName)) {
                $userName = $userId;
            }
            $this->userNames[$userId] = $userName;
        }

        return $this->userNames[$userId];
    }
}
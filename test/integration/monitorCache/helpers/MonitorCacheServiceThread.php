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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoProctoring\test\integration\monitorCache\helpers;

use oat\taoProctoring\test\monitorCache\mock\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\implementation\MonitorCacheService;

/**
 * Class used to execute concurrent queries to delivery monitoring storage
 *
 * Class MonitorCacheServiceThread
 * @package oat\taoProctoring\test\monitorCache\helpers
 */
class MonitorCacheServiceThread extends \Thread
{
    /**
     * @var MonitorCacheService
     */
    protected $service;

    /**
     * @var bool
     */
    protected $failed = false;

    /**
     * @var string Sample delivery execution id
     */
    protected $deliveryExecutionId = 'http://sample/first.rdf#i1450191587554175_test_record';

    /**
     * MonitorCacheServiceThread constructor.
     * @param $service MonitorCacheService
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * Run thread
     */
    public function run()
    {
        require_once dirname(__FILE__).'/../../../../tao/includes/raw_start.php';

        $deliveryExecution = $this->getDeliveryExecution();

        $data = [
            MonitorCacheService::COLUMN_TEST_TAKER => 'test_taker_id',
            MonitorCacheService::COLUMN_STATUS => 'active',
        ];

        $secondaryData = [
            'a' => '0',
            'b' => '1',
            'c' => '2',
            'd' => '3',
            'e' => '4',
            'f' => '5',
            'g' => '6',
            'h' => '7',
            'i' => '8',
            'j' => '9',
            'k' => '10',
            'l' => '11',
            'm' => '12',
            'n' => '13',
            'o' => '14',
            'p' => '15',
            'q' => '16',
            'r' => '17',
            's' => '18',
            't' => null,
        ];
        $dataModel = new DeliveryMonitoringData($deliveryExecution, []);
        foreach ($data as $key => $val) {
            $dataModel->addValue($key, $val);
        }
        usleep(rand(10000, 1500000));
        $this->service->save($dataModel);

        foreach ($secondaryData as $secKey => $secVal) {
            $dataModel->addValue($secKey, $secVal);
        }

        try {
            $this->service->save($dataModel);
            $dataModel->addValue('u', '20');
            $dataModel->addValue('a', '21', true);
            $this->service->save($dataModel);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->failed = true;
        };
    }

    /**
     * Whether saving of data has been filed
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * Get delivery execution mock
     * @return \oat\taoDelivery\model\execution\DeliveryExecution
     */
    protected function getDeliveryExecution()
    {
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize('oat\taoDelivery\model\execution\DeliveryExecution');
        $deliveryExecutionProphecy->getIdentifier()->willReturn($this->deliveryExecutionId);
        return $deliveryExecutionProphecy->reveal();
    }
}
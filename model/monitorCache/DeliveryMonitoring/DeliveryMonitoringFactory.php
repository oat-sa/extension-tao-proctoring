<?php

namespace oat\taoProctoring\model\monitorCache\implementation\DeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringFactory
{
    /** @var array */
    private $primaryColumns = [];

    public function __construct(array $primaryColumns)
    {
        $this->primaryColumns = $primaryColumns;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->primaryColumns;
    }

    /**
     * @param $array
     * @return DeliveryMonitoringEntity
     */
    public function buildEntityFromRawArray($array)
    {
        return new DeliveryMonitoringEntity(
            $array[$this->primaryColumns[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID]],
            [
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_STATUS]],
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_CURRENT_ASSESSMENT_ITEM]],
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_TEST_TAKER]],
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_AUTHORIZED_BY]],
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_START_TIME]],
                $array[$this->primaryColumns[MonitoringStorage::COLUMN_END_TIME]]
            ]
        );
    }
}
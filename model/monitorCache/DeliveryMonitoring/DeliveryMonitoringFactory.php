<?php

namespace oat\taoProctoring\model\monitorCache\DeliveryMonitoring;

use oat\taoProctoring\model\monitorCache\implementation\MonitoringStorage;

class DeliveryMonitoringFactory
{
    /** @var array */
    private $primaryColumns = [];

    /**
     * DeliveryMonitoringFactory constructor.
     * @param array $primaryColumns
     */
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
        $dataAttributes = [];

        foreach ($this->primaryColumns as $column) {
            $dataAttributes[$column] =  $this->issetColumnValue($array, $column);
        }

        return new DeliveryMonitoringEntity(
            $array[MonitoringStorage::COLUMN_DELIVERY_EXECUTION_ID],
            $dataAttributes
        );
    }

    /**
     * @param array $data
     * @param $key
     * @return mixed|null
     */
    protected function issetColumnValue(array $data, $key) {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }
}
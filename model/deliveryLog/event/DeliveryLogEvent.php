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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\taoProctoring\model\deliveryLog\event;


use oat\oatbox\event\Event;

class DeliveryLogEvent implements Event
{
    const EVENT_NAME = __CLASS__;

    public const EVENT_ID_TEST_EXIT_CODE = 'TEST_EXIT_CODE';
    public const EVENT_ID_SECTION_EXIT_CODE = 'SECTION_EXIT_CODE';
    public const EVENT_ID_TEST_PAUSE = 'TEST_PAUSE';
    public const EVENT_ID_TEST_CANCEL = 'TEST_CANCEL';
    public const EVENT_ID_TEST_RUN = 'TEST_RUN';
    public const EVENT_ID_TEST_RESUME = 'TEST_RESUME';
    public const EVENT_ID_TEST_AUTHORISE = 'TEST_AUTHORISE';
    public const EVENT_ID_TEST_TERMINATE = 'TEST_TERMINATE';
    public const EVENT_ID_TEST_IRREGULARITY = 'TEST_IRREGULARITY';

    /**
     * @var int unique identifier of the record in the delivery_log table
     */
    private $id;

    public function getName()
    {
        return self::EVENT_NAME;
    }

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}

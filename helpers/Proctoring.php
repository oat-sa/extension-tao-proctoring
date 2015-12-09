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

namespace oat\taoProctoring\helpers;

use oat\oatbox\user\User;

/**
 * This temporary helpers is a temporary way to return data to the controller.
 * This helps isolating the mock code from the real controller one.
 * It will be replaced by a real service afterward.
 */
class Proctoring
{
    /**
     * The default number of rows displayed in a data page
     */
    const DEFAULT_ROWS = 25;

    /**
     * The index of the default data page
     */
    const DEFAULT_PAGE = 1;

    /**
     * Paginates a list of items to render a data subset in a table
     * @param array $data
     * @param array $options
     * @param function $dataRenderer
     * @return array
     */
    public static function paginate($data, $options, $dataRenderer = null)
    {
        $amount = count($data);
        $rows = max(1, abs(ceil(isset($options['rows']) ? $options['rows'] : self::DEFAULT_ROWS)));
        $total = ceil($amount / $rows);
        $page = max(1, floor(min(isset($options['page']) ? $options['page'] : self::DEFAULT_PAGE, $total)));
        $start = ($page - 1) * $rows;
        $list = array();

        $data = array_slice($data, ($page - 1) * $rows, $rows);

        if ($dataRenderer) {
            $data = $dataRenderer($data);
        }

        return array(
            'offset' => $start,
            'length' => count($list),
            'amount' => $amount,
            'total' => $total,
            'page' => $page,
            'rows' => $rows,
            'data' => $data
        );
    }
}
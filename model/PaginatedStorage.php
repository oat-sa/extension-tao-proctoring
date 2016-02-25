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
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */

namespace oat\taoProctoring\model;

use oat\taoClientDiagnostic\model\storage\Storage;

/**
 * Interface PaginatedStorage
 * @package oat\taoProctoring\model
 */
interface PaginatedStorage extends Storage
{
    /**
     * The size of a page in the data set
     */
    const PAGE_SIZE = 25;

    /**
     * Gets an existing record in database by id
     * @param $id
     * @return mixed
     */
    public function find($id);

    /**
     * Gets a page from the storage model based on entity
     * @param int $page The page number
     * @param int $size The size of a page (number of rows)
     * @param array $filter A list of filters (pairs columns => value)
     * @return mixed
     */
    public function findPage($page = null, $size = PAGE_SIZE, $filter = null);

    /**
     * Gets the number of rows from the storage model based on entity
     * @param array $filter A list of filters (pairs columns => value)
     * @return int
     */
    public function count($filter = null);

    /**
     * Deletes a row from the storage model based on entity
     * @param $id
     * @param array $filter A list of filters (pairs columns => value)
     * @return mixed
     */
    public function delete($id, $filter = null);
}
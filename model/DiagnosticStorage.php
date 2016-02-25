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

namespace oat\taoProctoring\model;

use oat\taoClientDiagnostic\model\storage\Sql;
use oat\taoClientDiagnostic\exception\StorageException;

/**
 * Class DiagnosticStorage
 * @package oat\taoProctoring\model
 */
class DiagnosticStorage extends Sql implements PaginatedStorage
{
    /**
     * Deletes a row from the storage model based on entity
     * @param $id
     * @return bool
     * @throws StorageException
     */
    public function delete($id)
    {
        try {
            if (empty($id)) {
                throw new StorageException('Invalid id parameter.');
            }
            $persistence = $this->getPersistence();

            $query = 'DELETE FROM ' . self::DIAGNOSTIC_TABLE . ' WHERE ' . self::DIAGNOSTIC_ID . ' = ?';
            $statement = $persistence->query($query, array($id));
            return (boolean)$statement->rowCount();

        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Gets a page from the storage model based on entity
     * @param int $page The page number
     * @param int $size The size of a page (number of rows)
     * @return mixed
     * @throws StorageException
     */
    public function findPage($page = null, $size = PAGE_SIZE)
    {
        try {
            $persistence = $this->getPersistence();

            $offset = ($page - 1) * $size;

            $query = 'SELECT * FROM ' . self::DIAGNOSTIC_TABLE . ' LIMIT ' . $offset . ', ' . $size;
            $statement = $persistence->query($query);
            return $statement->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Gets the number of rows from the storage model based on entity
     * @return int
     * @throws StorageException
     */
    public function count()
    {
        try {
            $persistence = $this->getPersistence();

            $query = 'SELECT COUNT(*) FROM ' . self::DIAGNOSTIC_TABLE;
            $statement = $persistence->query($query);
            return $statement->fetchColumn();

        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }
}
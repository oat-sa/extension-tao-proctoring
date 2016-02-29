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

use Doctrine\DBAL\Driver\PDOStatement;
use oat\taoClientDiagnostic\model\storage\Sql;
use oat\taoClientDiagnostic\exception\StorageException;

/**
 * Class DiagnosticStorage
 * @package oat\taoProctoring\model
 */
class DiagnosticStorage extends Sql implements PaginatedStorage
{
    /**
     * Additional columns of diagnostic storage
     */
    const DIAGNOSTIC_WORKSTATION = 'workstation';
    const DIAGNOSTIC_TEST_CENTER = 'test_center';

    /**
     * Gets an existing record in database by id
     * @param $id
     * @return mixed
     * @throws StorageException
     */
    public function find($id)
    {
        if (empty($id)) {
            throw new StorageException('Invalid id parameter.');
        }

        try {
            return $this->select(null, [self::DIAGNOSTIC_ID => $id], 1)->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Gets a page from the storage model based on entity
     * @param int $page The page number
     * @param int $size The size of a page (number of rows)
     * @param array $filter A list of filters (pairs columns => value)
     * @return mixed
     * @throws StorageException
     */
    public function findPage($page = null, $size = PAGE_SIZE, $filter = null)
    {
        try {
            $offset = ($page - 1) * $size;
            return $this->select(null, $filter, $size, $offset)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Gets the number of rows from the storage model based on entity
     * @param array $filter A list of filters (pairs columns => value)
     * @return int
     * @throws StorageException
     */
    public function count($filter = null)
    {
        try {
            return $this->select('COUNT(*)', $filter)->fetchColumn();
        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Deletes a row from the storage model based on entity
     * @param $id
     * @param array $filter A list of filters (pairs columns => value)
     * @return bool
     * @throws StorageException
     */
    public function delete($id, $filter = null)
    {
        if (empty($id)) {
            throw new StorageException('Invalid id parameter.');
        }

        if (!$filter) {
            $filter = [];
        }

        $filter[self::DIAGNOSTIC_ID] = $id;

        try {
            \common_Logger::i('Deleting diagnostic result ' . $id);
            $query = 'DELETE FROM ' . self::DIAGNOSTIC_TABLE;
            return (boolean)$this->query($query, $filter)->rowCount();
        } catch (\PDOException $e) {
            throw new StorageException($e->getMessage());
        }
    }

    /**
     * Builds and runs a select query
     * @param array $columns
     * @param array $where
     * @param int $size
     * @param int $offset
     * @return PDOStatement
     */
    protected function select($columns = null, $where = null, $size = null, $offset = null)
    {
        if (!$columns) {
            $columns = '*';
        }
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $query = 'SELECT ' . implode(',', $columns) . ' FROM ' . self::DIAGNOSTIC_TABLE;

        return $this->query($query, $where, $size, $offset);
    }

    /**
     * Builds and runs a query
     * @param string $query
     * @param array $where
     * @param int $size
     * @param int $offset
     * @return PDOStatement
     */
    protected function query($query, $where = null, $size = null, $offset = null)
    {
        $params = [];

        if (is_array($where)) {
            $conditions = [];
            foreach($where as $column => $value) {
                $placeholder = '?';
                $value = '' . $value;

                $conditions[] = $column . ' = ' . $placeholder;
                $params[] = $value;
            }
            if (count($conditions)) {
                $query .= ' WHERE ' . implode(' AND ', $conditions);
            }
        }

        if (!is_null($size)) {
            $query = $this->getPersistence()->getPlatForm()->limitStatement($query, $size, $offset);
        }

        return $this->getPersistence()->query($query, $params);
    }
}
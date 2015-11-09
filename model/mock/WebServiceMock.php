<?php
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 */

namespace oat\taoProctoring\model\mock;

/**
 * Class WebServiceMock
 * @package oat\taoProctoring\model
 */
class WebServiceMock
{
    /**
     * Loads JSON data from a file.
     *
     * @param string $filePath
     * @return mixed
     */
    public static function loadJSON($filePath) {
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $data = json_decode($json, true);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Saves data as JSON into a file.
     *
     * @param string $filePath
     * @param mixed $data
     */
    public static function saveJSON($filePath, $data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $json);
    }

    /**
     * Gets a hash table from a collection using a particular key from each item.
     *
     * @param array $collection The collection to map
     * @param string $key The name of the item attribute to use as a key for the item
     * @return array Returns an associative array built from the provided collection
     */
    public static function map($collection, $key = 'id') {
        $result = array();
        if (is_array($collection)) {
            foreach($collection as $idx => $row) {
                if (isset($row[$key])) {
                    $idx = $row[$key];
                }
                $result[$idx] = $row;
            }
        }
        return $result;
    }

    /**
     * Filters items from a collection
     *
     * @param array $collection The collection to filter
     * @param string $key The name of the item attribute to filter
     * @param mixed $value The value that's select the items
     * @return array
     */
    public static function filterBy($collection, $key, $value) {
        return self::filter($collection, function($row) use($key, $value) {
            return isset($row[$key]) && !strnatcasecmp($value, $row[$key]);
        });
    }

    /**
     * Filters items from a collection using a callback function
     *
     * @param array $collection The collection to filter
     * @param function $callback The callback function that's select the items
     * @return array
     */
    public static function filter($collection, $callback) {
        $result = array();
        if (is_array($collection)) {
            $result = array_filter($collection, $callback);
        }
        return $result;
    }

    /**
     * Get an item from a random place in a collection
     * @param array $collection
     * @return mixed
     */
    public static function random($collection) {
        if (count($collection)) {
            return $collection[array_rand($collection)];
        }
        return array();
    }

    /**
     * Extract a page from a collection
     *
     * @param array $collection The collection to slice
     * @param int $page The page number
     * @param int $rows The size of a page (default: 25)
     * @param bool $dataOnly Tells if the method must only return the collection slice (default: false)
     * @return array Returns a page descriptor
     * @return array.offset The position of the first returned item in the collection
     * @return array.length The amount of returned records
     * @return array.amount The total amount of records in the collection
     * @return array.total The amount of available pages
     * @return array.page The index of the returned page
     * @return array.data The slice of the collection corresponding to needed page
     */
    public static function paginate($collection, $page, $rows = 25, $dataOnly = false) {
        $amount = count($collection);
        $rows = max(1, abs(ceil($rows)));
        $total = ceil($amount / $rows);
        $page = max(1, floor(min($page, $total)));
        $start = ($page - 1) * $rows;
        $data = array();

        if (is_array($collection)) {
            $data = array_slice($collection, $start, $rows);
        }

        if (!$dataOnly) {
            $data = array(
                'offset' => $start,
                'length' => count($data),
                'amount' => $amount,
                'total'  => $total,
                'page'   => $page,
                'rows'   => $rows,
                'data'   => $data,
            );
        }

        return $data;
    }

    /**
     * Sorts a collection by keys
     *
     * @param array $collection The collection to sort
     * @param array|string $keys A key or a list of keys to order
     */
    public static function sort(&$collection, $keys) {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $sortKeys = array();
        foreach($keys as $key => $dir) {
            if (is_numeric($key)) {
                $key = $dir;
                $dir = 1;
            }
            if (!is_numeric($dir)) {
                switch (strtolower($dir)) {
                    case 'desc':
                        $dir = -1;
                        break;

                    default:
                    case 'asc':
                        $dir = 1;
                        break;
                }
            } else {
                $dir = min(1, max(-1, ceil($dir)));
                if (!$dir) {
                    $dir = 1;
                }
            }

            $sortKeys[$key] = $dir;
        }

        if (is_array($collection)) {
            uasort($collection, function($a, $b) use($sortKeys) {
                foreach($sortKeys as $key => $dir) {
                    $av = isset($a[$key]) ? (string) $a[$key] : null;
                    $bv = isset($b[$key]) ? (string) $b[$key] : null;
                    $res = $dir * strnatcasecmp($av, $bv);
                    if ($res) {
                        return $res;
                    }
                }
                return 0;
            });
        }
    }

    /**
     * Applies options on a collection
     *
     * @param array $collection The collection on which apply the options
     * @param array $options A list of options to apply
     * @return array
     */
    public static function applyOptions(&$collection, $options) {
        if (isset($options['filter'])) {
            $filter = $options['filter'];

            if (!is_array($filter)) {
                $filter = array('*' => $filter);
            }
            $collection = WebServiceMock::filter($collection, function($row) use($filter) {
                foreach($filter as $fieldName => $filterValue) {
                    if ('*' == $fieldName) {
                        foreach($row as $value) {
                            if (is_scalar($value)) {
                                if (false !== stripos((string) $value, $filterValue)) {
                                    return true;
                                }
                            }
                        }
                    } else if (isset($row[$fieldName]) && false !== stripos((string) $row[$fieldName], $filterValue)) {
                        return true;
                    }
                }
                return false;
            });
        }

        if (isset($options['sortBy'])) {
            $key = $options['sortBy'];
            $dir = isset($options['sortOrder']) ? $options['sortOrder'] : 'asc';

            WebServiceMock::sort($collection, array($key => $dir));
        }

        if (isset($options['page'])) {
            $page = $options['page'];
            $size = isset($options['rows']) ? $options['rows'] : 25;
            $collection = WebServiceMock::paginate($collection, $page, $size);

            if (isset($options['sortBy'])) {
                $collection['sortby'] = $key;
                $collection['sortorder'] = $dir;
            }
        }

        return $collection;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: jsc
 * Date: 06/11/15
 * Time: 12:11
 */

namespace oat\taoProctoring\helpers;

use oat\oatbox\user\User;

class Proctoring
{
    /**
     * Paginates a list of items to render a data subset in a table
     * @param array $data
     * @param array $options
     * @return array
     */
    public static function paginate($data, $options)
    {
        $amount = count($data);
        $rows = max(1, abs(ceil(isset($options['rows']) ? $options['rows'] : 25)));
        $total = ceil($amount / $rows);
        $page = max(1, floor(min(isset($options['page']) ? $options['page'] : 1, $total)));
        $start = ($page - 1) * $rows;
        $list = array();

        $data = array_slice($data, ($page - 1) * $rows, $rows);

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

    /**
     * Gets the value of a string property from a user
     * @param User $user
     * @param string $property
     * @return mixed|string
     */
    public static function getUserStringProp($user, $property)
    {
        $value = $user->getPropertyValues($property);
        return empty($value) ? '' : current($value);
    }

    /**
     * Gets the full user name
     * @param User $user
     * @return string
     */
    public static function getUserName($user)
    {
        $firstName = self::getUserStringProp($user, PROPERTY_USER_FIRSTNAME);
        $lastName = self::getUserStringProp($user, PROPERTY_USER_LASTNAME);
        if (empty($firstName) && empty($lastName)) {
            $firstName = self::getUserStringProp($user, RDFS_LABEL);
        }

        return trim($firstName . ' ' . $lastName);
    }
}
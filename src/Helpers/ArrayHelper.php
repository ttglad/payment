<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:17 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Helpers;


class ArrayHelper
{
    /**
     * @param array $param
     * @return array
     */
    public static function paramFilter(array $param)
    {
        $paramFilter = [];
        foreach ($param as $key => $val) {
            if ($val === '' || $val === null) {
                continue;
            }
            if (!is_array($param[$key])) {
                $param[$key] = is_bool($param[$key]) ? $param[$key] : trim($param[$key]);
            }

            $paramFilter[$key] = $param[$key];
        }

        return $paramFilter;
    }

    /**
     * @param $array
     * @return string
     */
    public static function array2string($array)
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }

    /**
     * @param array $inputs
     * @param $keys
     * @return array
     */
    public static function removeKeys(array $inputs, $keys)
    {
        if (!is_array($keys)) {// 如果不是数组，需要进行转换
            $keys = explode(',', $keys);
        }

        if (empty($keys) || !is_array($keys)) {
            return $inputs;
        }

        $flag = true;
        foreach ($keys as $key) {
            if (array_key_exists($key, $inputs)) {
                if (is_int($key)) {
                    $flag = false;
                }
                unset($inputs[$key]);
            }
        }

        if (!$flag) {
            $inputs = array_values($inputs);
        }
        return $inputs;
    }
}

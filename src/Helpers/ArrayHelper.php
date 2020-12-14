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
}

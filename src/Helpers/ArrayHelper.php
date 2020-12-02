<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:17 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Helpers;


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
}
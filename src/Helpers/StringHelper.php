<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/1 5:52 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Helpers;


use Paymenet\Codes\PaymentCode;
use Payment\Exceptions\PaymentException;

class StringHelper
{
    /**
     * @param $params
     * @return false|string
     * @throws PaymentException
     */
    public static function createLinkString($params)
    {
        if (!is_array($params)) {
            throw new PaymentException('必须传入数组参数', PaymentCode::PARAM_ERROR);
        }

        reset($para);
        $arg = '';
        foreach ($para as $key => $val) {
            if (is_array($val)) {
                continue;
            }

            $arg .= $key . '=' . urldecode($val) . '&';
        }
        //去掉最后一个&字符
        $arg && $arg = substr($arg, 0, -1);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }
}
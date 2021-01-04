<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/1 5:52 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Helpers;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;

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
        ksort($params);
        reset($params);

        $arg = '';
        foreach ($params as $key => $val) {
            if (is_array($val) || self::checkEmpty($val)) {
                continue;
            }
            $arg .= $key . '=' . ($val) . '&';
        }
        //去掉最后一个&字符
        $arg && $arg = substr($arg, 0, -1);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * @param $data
     * @param $targetCharset
     * @return false|string|string[]|null
     */
    public static function charSet($data, $targetCharset)
    {

        if (!empty($data)) {
            $fileType = 'UTF-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    /**
     * @param $value
     * @return bool
     */
    public static function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (trim($value) === "") {
            return true;
        }
        return false;
    }

    /**
     * 0x转高精度数字
     * @param $hex
     * @return int|string
     */
    public static function hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    /**
     * @param array $params
     * @param string $action
     * @param string $code
     * @return string
     */
    public static function createHtml(array $params = [], string $action = '', string $code = 'UTF-8')
    {
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$code}" />
</head>
<body  onload="javascript:document.pay_form.submit();">
    <form id="pay_form" style="display:none" name="pay_form" action="{$action}" method="post">

eot;
        foreach ($params as $key => $value) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
    <input type="submit" type="hidden">
    </form>
</body>
</html>
eot;
        return $html;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 4:12 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Helpers;

use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;

class EncryptHelper
{

    /**
     * 获取公钥或者私钥信息
     * @param $key
     * @param string $type
     * @return string|null
     */
    public static function getRsaKeyValue($key, $type = 'private')
    {
        if (is_file($key)) {// 是文件
            $keyStr = @file_get_contents($key);
        } else {
            $keyStr = $key;
        }
        if (empty($keyStr)) {
            return null;
        }

        $keyStr = str_replace(PHP_EOL, '', $keyStr);
        if ($type === 'private') {
            $beginStr = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL;
            $endStr = PHP_EOL . '-----END RSA PRIVATE KEY-----';
        } else {
            $beginStr = '-----BEGIN PUBLIC KEY-----' . PHP_EOL;
            $endStr = PHP_EOL . '-----END PUBLIC KEY-----';
        }

        return $beginStr . wordwrap($keyStr, 64, "\n", true) . $endStr;
    }


    /**
     * @param $data
     * @return string
     */
    public static function der2pem($data)
    {
        $pem = chunk_split(base64_encode($data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }


    /**
     * @param $string
     * @param $key
     * @return string
     */
    public static function md5Encrypt($key, $string)
    {
        $string = $string . $key;
        return md5($string);
    }

    /**
     * @param $string
     * @param $sign
     * @param $key
     * @return bool
     */
    public static function md5Verify($key, $string, $sign)
    {
        $string = $string . $key;

        if (md5($string) == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $key
     * @param $data
     * @return string
     * @throws PaymentException
     */
    public static function rsaEncrypt($key, $data)
    {
        if (empty($key)) {
            return '';
        }

        $res = openssl_get_privatekey($key);
        if (empty($res)) {
            throw new PaymentException('您使用的私钥格式错误，请检查RSA私钥配置', PaymentCode::SIGN_ERROR);
        }

        openssl_sign($data, $sign, $res);
        openssl_free_key($res);

        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }


    /**
     * @param $key
     * @param $content
     * @return string
     * @throws PaymentException
     */
    public static function rsaDecrypt($key, $content)
    {
        if (empty($key)) {
            return '';
        }

        $res = openssl_get_privatekey($key);
        if (empty($res)) {
            throw new PaymentException('您使用的私钥格式错误，请检查RSA私钥配置');
        }

        //用base64将内容还原成二进制
        $content = base64_decode($content);
        //把需要解密的内容，按128位拆开解密
        $result = '';
        for ($i = 0; $i < strlen($content) / 128; $i++) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $result .= $decrypt;
        }
        openssl_free_key($res);
        return $result;
    }

    /**
     * @param $key
     * @param $data
     * @param $sign
     * @return bool
     * @throws PaymentException
     */
    public static function rsaVerify($key, $data, $sign)
    {
        // 初始时，使用公钥key
        $res = openssl_get_publickey($key);
        if (empty($res)) {
            throw new PaymentException('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
        }

        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA1);
        openssl_free_key($res);
        return $result;
    }

    /**
     * @param $key
     * @param $data
     * @return string
     * @throws PaymentException
     */
    public static function rsa2Encrypt($key, $data)
    {
        if (empty($key)) {
            return '';
        }

        $res = openssl_get_privatekey($key);
        if (empty($res)) {
            throw new \Exception('您使用的私钥格式错误，请检查RSA私钥配置');
        }

        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);

        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * @param $key
     * @param $content
     * @return string
     * @throws PaymentException
     */
    public static function rsa2Decrypt($key, $content)
    {
        if (empty($key)) {
            return '';
        }

        $res = openssl_get_privatekey($key);
        if (empty($res)) {
            throw new PaymentException('您使用的私钥格式错误，请检查RSA私钥配置');
        }

        //用base64将内容还原成二进制
        $decodes = base64_decode($content);

        $str = '';
        $dcyCont = '';
        foreach ($decodes as $n => $decode) {
            if (!openssl_private_decrypt($decode, $dcyCont, $res)) {
                echo '<br/>' . openssl_error_string() . '<br/>';
            }
            $str .= $dcyCont;
        }

        openssl_free_key($res);
        return $str;
    }

    /**
     * @param $key
     * @param $data
     * @param $sign
     * @return bool
     * @throws PaymentException
     */
    public static function rsa2Verify($key, $data, $sign)
    {
        // 初始时，使用公钥key
        $res = openssl_get_publickey($key);
        if (empty($res)) {
            throw new PaymentException('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
        }
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        return $result;
    }

}

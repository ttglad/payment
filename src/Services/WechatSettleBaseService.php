<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/14 6:05 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\StringHelper;

abstract class WechatSettleBaseService extends BaseService
{
    /**
     * 微信接口成功code定义
     */
    const REQ_SUCCESS = 'SUCCESS';

    /**
     * @var string
     */
    protected $gatewayUrl = '';

    /**
     * @var mixed|null
     */
    protected $appId = '';

    /**
     * @var mixed|null
     */
    protected $merchantId = '';

    /**
     * @var mixed|null
     */
    private $merchantKey = '';

    /**
     * @var string
     */
    private $privateKey = '';

    /**
     * @var string
     */
    private $certKey = '';

    /**
     * @var string
     */
    private $certs = '';

    /**
     * @var string
     */
    private $serialNo = '';

    /**
     * @var array
     */
    protected $needsKey = [];

    /**
     * WechatBaseService constructor.
     */
    public function __construct()
    {
        $this->gatewayUrl = self::$config->get('gateway_url', 'https://api.mch.weixin.qq.com/%s');
        $this->appId = self::$config->get('app_id', '');
        $this->merchantId = self::$config->get('merchant_id', '');
        $this->merchantKey = self::$config->get('merchant_key', '');
        $this->privateKey = file_get_contents(self::$config->get('private_key', ''));
        $this->certKey = self::$config->get('cert_key', '');
        $this->certs = self::$config->get('certs', '');

        if (!empty($this->certs)) {
            foreach($this->certs as $_serialNo => $_certs) {
                $this->serialNo = $_serialNo;
                break;
            }
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param string $timestamp
     * @param string $nonce
     * @param string $body
     * @return string
     */
    protected function makeSign(string $url, string $method, string $timestamp, string $nonce, string $body)
    {
        try {
            $url_parts = parse_url($url);
            $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));

            $message = $method . "\n" .
                $canonical_url . "\n" .
                $timestamp . "\n" .
                $nonce . "\n" .
                $body . "\n";

            openssl_sign($message, $raw_sign, openssl_get_privatekey($this->privateKey), 'sha256WithRSAEncryption');
            $sign = base64_encode($raw_sign);

            $token = sprintf('WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
                $this->merchantId, $nonce, $timestamp, $this->getSerialNo($this->certKey), $sign);

        } catch (Exception $e) {
            throw $e;
        }

        return $token;
    }

    /**
     * @param string $message
     * @param string $signature
     * @param string $serialNo
     * @return false|int
     */
    protected function verifySign(string $message = '', string $signature = '', string $serialNo = '')
    {
        if (empty($serialNo)) {
            throw new PaymentException('缺少参数serial_no!', PaymentCode::VERIFY_ERROR);
        }

        $publicKey = $this->getCertContentBySerialNo($serialNo);

        if (!in_array('sha256WithRSAEncryption', \openssl_get_md_methods(true))) {
            throw new PaymentException('当前PHP环境不支持SHA256withRSA', PaymentCode::VERIFY_ERROR);
        }
        $signature = base64_decode($signature);
        $verify = openssl_verify($message, $signature, openssl_get_publickey($publicKey), 'sha256WithRSAEncryption');
        if (!$verify) {
            throw new PaymentException('验签失败', PaymentCode::VERIFY_ERROR);
        }
    }

    /**
     * @param string $associatedData
     * @param string $nonceStr
     * @param string $ciphertext
     * @param string $aesKey
     * @return false|string
     * @throws PaymentException
     * @throws \SodiumException
     */
    protected function decryptToString(
        string $associatedData,
        string $nonceStr,
        string $ciphertext,
        string $aesKey = ''
    ) {
        if (empty($aesKey)) {
            $aesKey = $this->merchantKey;
        }
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) <= 16) {
            return false;
        }

        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
            \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
            \Sodium\crypto_aead_aes256gcm_is_available()) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

            return \openssl_decrypt($ctext, 'aes-256-gcm', $aesKey, \OPENSSL_RAW_DATA, $nonceStr,
                $authTag, $associatedData);
        }

        throw new PaymentException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php', PaymentCode::CONFIG_ERROR);
    }

    /**
     * @param string $url
     * @param string $body
     * @param string $method
     * @return string[]
     */
    protected function getCurlHeader(string $url = '', string $body = '', string $method = '')
    {
        $timestamp = time();
        $nonce = StringHelper::getNonceStr();
        $sign = $this->makeSign($url, $method, $timestamp, $nonce, $body);

        $header = [
            'Authorization' => $sign,
            'Accept' => 'application/json',
            'User-Agent' => $this->merchantId,
            'Content-Type' => 'application/json',
        ];
        if ($this->serialNo) {
            $header['Wechatpay-Serial'] = $this->serialNo;
        }

        return $header;
    }


    /**
     * @param string $certPath
     * @return mixed
     */
    private function getSerialNo(string $certPath = '')
    {
        $cert = file_get_contents($certPath);
        $certArray = openssl_x509_parse($cert);
        return $certArray['serialNumberHex'];
    }


    /**
     * @param array $param
     * @param array $needParam
     * @throws PaymentException
     */
    protected function checkParam(array $param = [], array $needParam = [])
    {
        if (!empty($needParam)) {
            foreach ($needParam as $item) {
                if (!isset($param["$item"]) || StringHelper::checkEmpty($param["$item"])) {
                    throw new PaymentException(sprintf('key [%s] is need', $item), PaymentCode::PARAM_ERROR);
                }
            }
        }
    }


    /**
     * @param string $serialNo
     * @return mixed|string
     */
    protected function getCertContentBySerialNo(string $serialNo = '')
    {
        $return = '';
        if ($serialNo && !empty($this->certs)) {
            foreach ($this->certs as $_serialNo => $_certs) {
                if ($_serialNo == $serialNo) {
                    $return = $_certs;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getSelfParams(array $requestParams);
}

<?php

/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 3:46 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services;

use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\EncryptHelper;
use Ttglad\Payment\Helpers\StringHelper;
use Ttglad\Payment\Helpers\ArrayHelper;

/**
 * Class AlipayBaseService
 * @package Paymenet\Services
 */
abstract class AlipayBaseService extends BaseService
{
    /**
     * 支付宝接口成功code定义
     */
    const REQ_SUCCESS = '10000';

    /**
     * @var string
     * 普通公钥 normal
     * 公钥证书 cert
     */
    protected $keyType = 'normal';

    /**
     * 秘钥
     * @var string
     */
    protected $privateKey = '';

    /**
     * 公钥
     * @var string
     */
    protected $publicKey = '';

    /**
     * @var mixed|string|null
     */
    protected $appCertPath = '';

    /**
     * @var mixed|string|null
     */
    protected $rootCertPath = '';

    /**
     * 网关地址
     * @var string
     */
    protected $gatewayUrl = '';

    /**
     * @var array
     */
    protected $bizContentKey = [];

    /**
     * AlipayBaseService constructor.
     * @throws PaymentException
     */
    public function __construct()
    {
        // 获取证书模式
        $this->keyType = self::$config->get('key_type', 'normal');

        if ($this->keyType == 'cert') {
            $this->appCertPath = self::$config->get('app_cert_path', '');
            $this->rootCertPath = self::$config->get('root_cert_path', '');
        } elseif ($this->keyType == 'normal') {

        } else {
            throw new PaymentException('config param [key_type] is error', PaymentCode::CONFIG_ERROR);
        }

        // 获取公钥
        $publicKeyData = self::$config->get('ali_public_key', '');
        if (empty($publicKeyData)) {
            throw new PaymentException('config param [ali_public_key] is null', PaymentCode::CONFIG_ERROR);
        }
        $this->publicKey = EncryptHelper::getRsaKeyValue($publicKeyData, 'public');
        if (empty($this->publicKey)) {
            throw new PaymentException('param [ali_public_key] is null', PaymentCode::PARAM_ERROR);
        }

        // 获取私钥
        $privateKeyData = self::$config->get('ali_private_key', '');
        if (empty($privateKeyData)) {
            throw new PaymentException('config param [ali_private_key] is null', PaymentCode::CONFIG_ERROR);
        }
        $this->privateKey = EncryptHelper::getRsaKeyValue($privateKeyData, 'private');
        if (empty($this->privateKey)) {
            throw new PaymentException('param [ali_private_key] is null', PaymentCode::PARAM_ERROR);
        }

        $this->gatewayUrl = self::$config->get('gateway_url', 'https://openapi.alipay.com/gateway.do');
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     * @throws PaymentException
     */
    public function buildParam(string $method, array $params)
    {
        $bizContent = $this->getBizContent($params);
        $requestParams = $this->getPublicData($method, $bizContent);

        $this->checkParam($bizContent, $this->bizContentKey);

        ksort($requestParams);

        try {
            $signStr = StringHelper::createLinkString($requestParams);
            $signType = self::$config->get('sign_type', '');
            $requestParams['sign'] = $this->makeSign($signType, $signStr);
        } catch (\Exception $e) {
            throw new PaymentException($e->getMessage(), PaymentCode::PARAM_ERROR);
        }

        return $requestParams;
    }

    /**
     * @param string $signType
     * @param string $signString
     * @return string
     * @throws PaymentException
     */
    protected function makeSign(string $signType, string $signString)
    {
        switch (strtoupper($signType)) {
            case 'RSA':
                $sign = EncryptHelper::rsaEncrypt($this->privateKey, $signString);
                break;
            case 'RSA2':
                $sign = EncryptHelper::rsa2Encrypt($this->privateKey, $signString);
                break;
            default:
                throw new PaymentException(sprintf('[%s] sign type not support', $signType), PaymentCode::PARAM_ERROR);
        }
        return $sign;
    }

    /**
     * @param array $data
     * @param string $sign
     * @return bool
     * @throws PaymentException
     */
    protected function verifySign(array $data, string $sign)
    {
        try {
            $signType = self::$config->get('sign_type', '');
            $signData = json_encode($data, JSON_UNESCAPED_UNICODE);

            switch (strtoupper($signType)) {
                case 'RSA':
                    return EncryptHelper::rsaVerify($this->publicKey, $signData, $sign);
                case 'RSA2':
                    return EncryptHelper::rsa2Verify($this->publicKey, $signData, $sign);
                default:
                    throw new PaymentException(sprintf('[%s] sign type not support', $signType),
                        PaymentCode::PARAM_ERROR);
            }
        } catch (\Exception $e) {
            throw new PaymentException(sprintf('check ali pay sign failed, sign type is [%s]', $signType),
                PaymentCode::SIGN_ERROR, $data);
        }
    }

    /**
     * @param string $method
     * @param array $bizContent
     * @return array
     */
    private function getPublicData(string $method, array $bizContent)
    {
        $publicData = [
            'app_id' => self::$config->get('app_id', ''),
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => self::$config->get('sign_type', 'RSA2'),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
        ];

        // 证书模式
        if ($this->keyType == 'cert') {
            $publicData['app_cert_sn'] = $this->getCertSN($this->appCertPath);
            $publicData['alipay_root_cert_sn'] = $this->getRootCertSN($this->rootCertPath);
        }

        if (self::$config->get('return_url')) {
            $publicData['return_url'] = self::$config->get('return_url');
        }

        if (self::$config->get('notify_url')) {
            $publicData['notify_url'] = self::$config->get('notify_url');
        }

        if (self::$config->get('app_auth_token')) {
            $publicData['app_auth_token'] = self::$config->get('app_auth_token');
        }

        return $publicData;
    }

    /**
     * 从证书中提取序列号
     * @param $certPath
     * @return string
     */
    private function getCertSN($certPath)
    {
        $cert = file_get_contents($certPath);
        $ssl = openssl_x509_parse($cert);
        $SN = md5(ArrayHelper::array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 提取根证书序列号
     * @param $certPath
     * @return string|null
     */
    private function getRootCertSN($certPath)
    {
        $cert = file_get_contents($certPath);
        $array = explode('-----END CERTIFICATE-----', $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = StringHelper::hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == 'sha1WithRSAEncryption' || $ssl[$i]['signatureTypeLN'] == 'sha256WithRSAEncryption') {
                if ($SN == null) {
                    $SN = md5(ArrayHelper::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {
                    $SN = $SN . '_' . md5(ArrayHelper::array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
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
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getBizContent(array $requestParams);
}

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
abstract class AlipayGlobalBaseService extends BaseService
{
    /**
     * 支付宝接口成功code定义
     */
    const REQ_SUCCESS = '10000';

    /**
     * @var string
     * md5
     * rsa
     */
    protected $signType = 'md5';

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
     * @var string
     */
    protected $md5Key = '';


    /**
     * @var string
     */
    protected $caPath = '';


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
        $this->signType = strtoupper(self::$config->get('sign_type', 'RSA'));

        if ($this->signType == 'RSA') {
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
        } elseif ($this->signType == 'md5') {
            $this->md5Key = self::$config->get('md5_key');
        } else {
            throw new PaymentException('config param [sign_type] is error', PaymentCode::CONFIG_ERROR);
        }

        $this->gatewayUrl = self::$config->get('gateway_url', 'https://intlmapi.alipay.com/gateway.do');
        $this->caPath = self::$config->get('ca_path', '');
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

        $requestParams = array_merge($requestParams, $bizContent);

        ksort($requestParams);

        try {
            $signStr = StringHelper::createLinkString($requestParams, ['sign_type']);
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
            case 'MD5':
                $sign = EncryptHelper::md5Encrypt($this->md5Key, $signString);
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
//            $signData = json_encode($data, JSON_UNESCAPED_UNICODE);
            $signData = StringHelper::createLinkString($data, ['sign', 'sign_type']);

            switch (strtoupper($signType)) {
                case 'RSA':
                    return EncryptHelper::rsaVerify($this->publicKey, $signData, $sign);
                case 'MD5':
                    return EncryptHelper::md5Verify($this->md5Key, $signData, $sign);
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
            'partner' => self::$config->get('app_id', ''),
            'service' => $method,
            '_input_charset' => 'UTF-8',
            'sign_type' => $this->signType,
        ];

        if (self::$config->get('return_url')) {
            $publicData['return_url'] = self::$config->get('return_url');
        }

        if (self::$config->get('notify_url')) {
            $publicData['notify_url'] = self::$config->get('notify_url');
        }

        return $publicData;
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

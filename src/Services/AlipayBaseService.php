<?php

/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 3:46 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Paymenet\Services;

use Paymenet\Codes\PaymentCode;
use Payment\Exceptions\PaymentException;
use Payment\Helpers\EncryptHelper;
use Payment\Helpers\StringHelper;

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
     * 网关地址
     * @var string
     */
    protected $gatewayUrl = '';

    /**
     * AlipayBaseService constructor.
     * @throws PaymentException
     */
    public function __construct()
    {
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

        try {
            $signStr = StringHelper::createLinkString($params);
            $signType = self::$config->get('sign_type', '');
            $requestParams['sign'] = $this->makeSign($signType, $signStr);
        } catch (\Exception $e) {
            throw new PaymentException($e->getMessage(), Payment::PARAMS_ERR);
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
                $sign = EncryptHelper::rsaEncrypt($this->publicKey, $signString);
                break;
            case 'RSA2':
                $sign = EncryptHelper::rsa2Encrypt($this->publicKey, $signString);
                break;
            default:
                throw new PaymentException(printf('[%s] sign type not support', $signType), PaymentCode::PARAM_ERROR);
        }
        return $sign;
    }

    /**
     * @param string $method
     * @param array $bizContent
     * @return array
     */
    private function getPublicData(string $method, array $bizContent)
    {
        return [
            'app_id' => self::$config->get('app_id', ''),
            'method' => $method,
            'format' => 'JSON',
            'return_url' => self::$config->get('return_url', ''),
            'charset' => 'utf-8',
            'sign_type' => self::$config->get('sign_type', ''),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => self::$config->get('notify_url', ''),
            // 'app_auth_token' => '', // 暂时不用
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getBizContent(array $requestParams);
}

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
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Helpers\StringHelper;

abstract class WechatBaseService extends BaseService
{
    /**
     * 微信接口成功code定义
     */
    const REQ_SUCCESS = 'SUCCESS';

    /**
     * @var string
     */
    private $gatewayUrl = '';

    /**
     * @var mixed|null
     */
    private $merchantKey = '';

    /**
     * @var mixed|null
     */
    private $nonceStr = '';

    /**
     * @var mixed|null
     */
    private $signType = '';

    /**
     * @var array
     */
    protected $needsKey = [];

    /**
     * @var array
     */
    protected $requestOptions = [];

    /**
     * WechatBaseService constructor.
     */
    public function __construct()
    {
        $this->gatewayUrl = self::$config->get('gateway_url', 'https://api.mch.weixin.qq.com/%s');
        $this->merchantKey = self::$config->get('merchant_key', '');
        $this->signType = self::$config->get('sign_type', 'MD5');
        $this->nonceStr = self::$config->get('nonce_str', StringHelper::getNonceStr());
    }

    /**
     * @param array $requestParams
     * @return string
     * @throws PaymentException
     */
    public function buildParam(array $requestParams)
    {
        try {
            $params = [
                'appid' => self::$config->get('app_id', ''),
                'sub_appid' => self::$config->get('sub_appid', ''),
                'mch_id' => self::$config->get('mch_id', ''),
                'sub_mch_id' => self::$config->get('sub_mch_id', ''),
                'nonce_str' => $this->nonceStr,
                'sign_type' => $this->signType,
            ];

            if (!empty($requestParams)) {
                $selfParams = $this->getSelfParams($requestParams);

                if (is_array($selfParams) && !empty($selfParams)) {
                    $params = array_merge($params, $selfParams);
                }
            }
            $params = ArrayHelper::paramFilter($params);


            $signStr = StringHelper::createLinkString($params);
            $params['sign'] = $this->makeSign($signStr);

            $this->checkParam($params, $this->needsKey);
        } catch (Exception $e) {
            throw new PaymentException($e->getMessage(), PaymentCode::PARAM_ERROR);
        }

        $xmlData = DataHelper::toXml($params);
        if ($xmlData === false) {
            throw new PaymentException('error generating xml', PaymentCode::XML_FORMAT_ERROR);
        }

        return $xmlData;
    }

    /**
     * @param array $goods
     * @return array
     */
    protected function formatGoodsInfo(array $goods = [])
    {
        $return = [];
        if (!empty($goods)) {
            foreach ($goods as $_goods) {
                if (!isset($_goods['goods_id'])) {
                    continue;
                }
                $info =  [
                    'goods_id' => $_goods['goods_id'] ?? '',
                    'wxpay_goods_id' => $_goods['goods_id_third'] ?? '',
                    'goods_name' => $_goods['goods_name'] ?? '',
                    'quantity' => $_goods['goods_quantity'] ?? '',
                    'price' => DataHelper::amountFormat($_goods['goods_price']),
                ];
                $return[] = $info;
            }
        }
        return $return;
    }

    /**
     * @param string $signStr
     * @return string
     */
    protected function makeSign(string $signStr)
    {
        try {
            switch (strtoupper($this->signType)) {
                case 'MD5':
                    $signStr .= '&key=' . $this->merchantKey;
                    $sign = md5($signStr);
                    break;
                case 'HMAC-SHA256':
                    $signStr .= '&key=' . $this->merchantKey;
                    $sign = strtoupper(hash_hmac('sha256', $signStr, $this->merchantKey));
                    break;
                default:
                    throw new PaymentException(sprintf('[%s] sign type not support', $this->signType),
                        PaymentCode::PARAM_ERROR);
            }
        } catch (PaymentException $e) {
            throw $e;
        }

        return strtoupper($sign);
    }

    /**
     * @param string $method
     * @param array $requestParams
     * @return array|false
     * @throws PaymentException
     */
    protected function requestXml(string $method, array $requestParams)
    {
        try {
            $xmlData = $this->buildParam($requestParams);
            $url = sprintf($this->gatewayUrl, $method);

            $resXml = HttpHelper::postXML($url, $xmlData, [], 3, $this->getRequestOptions());

            $resArr = DataHelper::toArray($resXml);
            if (!is_array($resArr) || $resArr['return_code'] !== self::REQ_SUCCESS) {
                throw new PaymentException($resArr['return_msg'] ?? 'error', PaymentCode::WECHAT_TIMEOUT, $resArr);
            } elseif (isset($resArr['result_code']) && $resArr['result_code'] !== self::REQ_SUCCESS) {
                throw new PaymentException(sprintf('code:%d, desc:%s', $resArr['err_code'], $resArr['err_code_des']),
                    PaymentCode::WECHAT_CHECK_FAILED, $resArr);
            }

            if (isset($resArr['sign']) && $this->verifySign($resArr) === false) {
                throw new PaymentException('check return data sign failed', PaymentCode::SIGN_ERROR, $resArr);
            }

            return $resArr;
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return bool
     * @throws PaymentException
     */
    private function verifySign(array $data)
    {
        try {
            $retSign = $data['sign'];
            $values = ArrayHelper::removeKeys($data, ['sign']);
            $values = ArrayHelper::paramFilter($values);
            $signStr = StringHelper::createLinkstring($values);
        } catch (\Exception $e) {
            throw new PaymentException('wechat verify sign generate str get error', PaymentCode::SIGN_ERROR);
        }

        $signStr .= '&key=' . $this->merchantKey;
        switch (strtoupper($this->signType)) {
            case 'MD5':
                $sign = md5($signStr);
                break;
            case 'HMAC-SHA256':
                $sign = hash_hmac('sha256', $signStr, $this->merchantKey);
                break;
            default:
                $sign = '';
        }
        return strtoupper($sign) === $retSign;
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
     * @param array $options
     * @return array
     */
    protected function setRequestOptions(array $options = [])
    {
        $this->requestOptions = $options;
    }

    /**
     * @return mixed
     */
    protected function getRequestOptions()
    {
        return $this->requestOptions;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getSelfParams(array $requestParams);
}

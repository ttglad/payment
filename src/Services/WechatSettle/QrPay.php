<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 10:52 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\WechatSettle;

use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\WechatSettleConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\WechatSettleBaseService;

class QrPay extends WechatSettleBaseService implements IRequestContract
{
    protected $needsKey = [];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $url = sprintf($this->gatewayUrl, WechatSettleConst::QR_PAY_METHOD);
            $param = $this->getSelfParams($requestParams);
            $data = HttpHelper::postXml($url, json_encode($param), $this->getCurlHeader($url, json_encode($param), 'POST'), 3.0, [], false);

            $body = $data->getBody()->getContents();
            if (200 != $data->getStatusCode()) {
                $jsonData = json_decode($body, true);
                if (!empty($jsonData) && isset($jsonData['code'])) {
                    throw new PaymentException('http code is: ' . $data->getStatusCode() . ',code is: ' . $jsonData['code'] . ',message is: ' . $jsonData['message'], PaymentCode::ALIPAY_SETTLE_RESULT_FAILED);
                }
                throw new PaymentException('http code is: ' . $data->getStatusCode(), PaymentCode::ALIPAY_SETTLE_RESULT_FAILED);
            }

            $timestamp = $data->getHeader('WECHATPAY-TIMESTAMP')[0];
            $nonce = $data->getHeader('WECHATPAY-NONCE')[0];
            $signature = $data->getHeader('WECHATPAY-SIGNATURE')[0];
            $serialNo = $data->getHeader('WECHATPAY-SERIAL')[0];

            $message = "$timestamp\n$nonce\n$body\n";

            $this->verifySign($message, $signature, $serialNo);

            return ($body);
        } catch (PaymentException $e) {
            throw $e;
        }

        return $data;
    }


    /**
     * @param array $requestParams
     * @return array|mixed
     */
    public function getSelfParams(array $requestParams)
    {
        $nowTime = isset($requestParams['time_start']) ? strtotime($requestParams['time_start']) : time();
        if (isset($requestParams['time_expire'])) {
            $timeExpire = date('c', $requestParams['time_expire']);
        } else {
            $timeExpire = date('c', $nowTime + 7200);
        }

        $selfParams = [
            'combine_appid' => $this->appId,
            'combine_mchid' => $this->merchantId,
            'combine_out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'scene_info' => $requestParams['scene_info'] ?? '',
            'sub_orders' => $requestParams['sub_orders'] ?? '',
            'settle_info' => $requestParams['settle_info'] ?? '',
            'combine_payer_info' => $requestParams['combine_payer_info'] ?? '',
            'time_start' => date('c', $nowTime),
            'time_expire' => $timeExpire,
            'notify_url' => $requestParams['notify_url'] ?? '',
        ];

        return ArrayHelper::paramFilter($selfParams);
    }
}

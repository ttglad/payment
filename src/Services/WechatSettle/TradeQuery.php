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

class TradeQuery extends WechatSettleBaseService implements IRequestContract
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
            $param = $this->getSelfParams($requestParams);
            $url = sprintf($this->gatewayUrl, sprintf(WechatSettleConst::TRADE_QUERY_METHOD, $param['combine_out_trade_no']));
            $data = HttpHelper::get($url, [], $this->getCurlHeader($url, '', 'GET'), 3.0, [], false);

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
        $selfParams = [
            'combine_out_trade_no' => $requestParams['out_trade_no'] ?? '',
        ];

        return ArrayHelper::paramFilter($selfParams);
    }
}

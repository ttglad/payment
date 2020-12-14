<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/14 10:52 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Alipay;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\AlipayConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipayBaseService;

class QrPay extends AlipayBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_trade_no', 'total_amount', 'subject'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::QR_PAY_METHOD, $requestParams);

            $ret = HttpHelper::get($this->gatewayUrl, $param, [], 3);

            $retArray = json_decode($ret, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PaymentException(sprintf('format trade create get error, [%s]', json_last_error_msg()), PaymentCode::JSON_FORMAT_ERROR, ['raw' => $ret]);
            }

            $content = $retArray['alipay_trade_precreate_response'];
            if ($content['code'] !== self::REQ_SUCCESS) {
                throw new PaymentException(sprintf('request get failed, msg[%s], sub_msg[%s]', $content['msg'], $content['sub_msg']), PaymentCode::SIGN_ERROR, $content);
            }

            $signFlag = $this->verifySign($content, $retArray['sign']);
            if (!$signFlag) {
                throw new PaymentException('check sign failed', PaymentCode::SIGN_ERROR, $retArray);
            }
            return $content;
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return array|mixed
     */
    protected function getBizContent(array $requestParams)
    {
        $timeoutExp = '';
        $timeExpire = intval($requestParams['time_expire']);
        if (!empty($timeExpire)) {
            $expire = floor(($timeExpire - time()) / 60);
            ($expire > 0) && $timeoutExp = $expire . 'm';// 超时时间 统一使用分钟计算
        }

        $bizContent = [
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'seller_id' => $requestParams['seller_id'] ?? '',
            'total_amount' => $requestParams['amount'] > 0 ? number_format($requestParams['amount'] / 100, 2) : '',
            'discountable_amount' => isset($requestParams['discountable_amount']) ? number_format($requestParams['discountable_amount'] / 100, 2) : '',
            'subject' => $requestParams['subject'] ?? '',
            'body' => $requestParams['body'] ?? '',
            'goods_detail' => $requestParams['goods_detail'] ?? '',
            'product_code' => $requestParams['product_code'] ?? '',
            'operator_id' => $requestParams['operator_id'] ?? '',
            'store_id' => $requestParams['store_id'] ?? '',
            'disable_pay_channels' => $requestParams['disable_pay_channels'] ?? '',
            'enable_pay_channels' => $requestParams['enable_pay_channels'] ?? '',
            'terminal_id' => $requestParams['terminal_id'] ?? '',
            'extend_params' => $requestParams['extend_params'] ?? '',
            'timeout_express' => $timeoutExp,
            'settle_info' => $requestParams['settle_info'] ?? '',
            'merchant_order_no' => $requestParams['merchant_order_no'] ?? '',
            'business_params' => $requestParams['business_params'] ?? '',
            'qr_code_timeout_express' => $requestParams['qr_code_timeout_express'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

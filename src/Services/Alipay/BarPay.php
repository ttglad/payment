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

class BarPay extends AlipayBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_trade_no', 'scene', 'auth_code', 'total_amount', 'subject'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::BAR_PAY_METHOD, $requestParams);

            $ret = HttpHelper::get($this->gatewayUrl, $param, [], 3);

            $retArray = json_decode($ret, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PaymentException(sprintf('format trade create get error, [%s]', json_last_error_msg()), PaymentCode::JSON_FORMAT_ERROR, ['raw' => $ret]);
            }

            $content = $retArray['alipay_trade_pay_response'];
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
            'scene' => $requestParams['scene'] ?? 'bar_code', //条码支付
            'auth_code' => $requestParams['auth_code'] ?? '',
            'product_code' => $requestParams['product_code'] ?? '',
            'subject' => $requestParams['subject'] ?? '',
            'buyer_id' => $requestParams['buyer_id'] ?? '',
            'seller_id' => $requestParams['seller_id'] ?? '',
            'total_amount' => $requestParams['amount'] > 0 ? number_format($requestParams['amount'] / 100, 2) : '',
            'discountable_amount' => isset($requestParams['discountable_amount']) ? number_format($requestParams['discountable_amount'] / 100, 2) : '',
            'trans_currency' => $requestParams['trans_currency'] ?? 'CNY',
            'settle_currency' => $requestParams['settle_currency'] ?? 'CNY',
            'body' => $requestParams['body'] ?? '',
            'goods_detail' => $requestParams['goods_detail'] ?? '',
            'operator_id' => $requestParams['operator_id'] ?? '',
            'store_id' => $requestParams['store_id'] ?? '',
            'terminal_id' => $requestParams['terminal_id'] ?? '',
            'extend_params' => $requestParams['extend_params'] ?? '',
            'timeout_express' => $timeoutExp,
            'auth_confirm_mode' => $requestParams['auth_confirm_mode'] ?? '',
            'terminal_params' => $requestParams['terminal_params'] ?? '',
            'promo_params' => $requestParams['promo_params'] ?? '',
            'advance_payment_type' => $requestParams['advance_payment_type'] ?? '',
            'query_options' => $requestParams['query_options'] ?? '',
            'request_org_pid' => $requestParams['request_org_pid'] ?? '',
            'is_async_pay' => $requestParams['is_async_pay'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

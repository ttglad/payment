<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/7 6:21 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Alipay;


use Ttglad\Payment\Consts\AlipayConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Services\AlipayBaseService;

class WebPay extends AlipayBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_trade_no', 'product_code', 'total_amount', 'subject'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::WEP_PAY_METHOD, $requestParams);
            return $this->gatewayUrl . '?' . http_build_query($param);
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
            'product_code' => $requestParams['product_code'] ?? 'FAST_INSTANT_TRADE_PAY',
            'total_amount' => $requestParams['amount'] > 0 ? number_format($requestParams['amount'] / 100, 2) : '',
            'subject' => $requestParams['subject'] ?? '',
            'body' => $requestParams['body'] ?? '',
            'time_expire' => $timeExpire ? date('Y-m-d H:i:s', $timeExpire) : '',
            'goods_detail' => $requestParams['goods_detail'] ?? '',
            'passback_params' => urlencode($requestParams['return_params'] ?? ''),
            'extend_params' => $requestParams['extend_params'] ?? '',
            'goods_type' => $requestParams['goods_type'] ?? '',
            'timeout_express' => $timeoutExp,
            'promo_params' => $requestParams['promo_params'] ?? '',
            'sub_merchant' => $requestParams['sub_merchant'] ?? '',
            'merchant_order_no' => $requestParams['merchant_order_no'] ?? '',
            'enable_pay_channels' => '',
            'store_id' => $requestParams['store_id'] ?? '',
            'disable_pay_channels' => @implode(self::$config->get('limit_pay', ''), ','),
            'qr_pay_mode' => $requestParams['qr_pay_mode'] ?? '',
            'qrcode_width' => $requestParams['qrcode_width'] ?? '',
            'settle_info' => $requestParams['settle_info'] ?? '',
            'invoice_info' => $requestParams['invoice_info'] ?? '',
            'agreement_sign_params' => $requestParams['agreement_sign_params'] ?? '',
            'integration_type' => $requestParams['integration_type'] ?? '',
            'request_from_url' => $requestParams['request_from_url'] ?? '',
            'business_params' => $requestParams['business_params'] ?? '',
            'ext_user_info' => $requestParams['ext_user_info'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

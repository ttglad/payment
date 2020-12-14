<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/3 5:05 下午
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

class WapPay extends AlipayBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_trade_no', 'product_code', 'total_amount', 'subject', 'quit_url'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::WAP_PAY_METHOD, $requestParams);
            return http_build_query($param);
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
            'body' => $requestParams['body'] ?? '',
            'subject' => $requestParams['subject'] ?? '',
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'timeout_express' => $timeoutExp,
            'time_expire' => $timeExpire ? date('Y-m-d H:i:s', $timeExpire) : '',
            'total_amount' => $requestParams['amount'] > 0 ? $requestParams['amount'] / 100 : '',
            'auth_token' => $requestParams['auth_token'] ?? '',
            'goods_type' => $requestParams['goods_type'] ?? '',
            'quit_url' => $requestParams['quit_url'] ?? '',
            'passback_params' => $requestParams['passback_params'] ?? '',
            'product_code' => $requestParams['product_code'] ?? 'QUICK_WAP_WAY',
            'promo_params' => $requestParams['promo_params'] ?? '',
            'extend_params' => $requestParams['extend_params'] ?? '',
            'merchant_order_no' => $requestParams['merchant_order_no'] ?? '',
            'enable_pay_channels' => '',
            'disable_pay_channels' => @implode(self::$config->get('limit_pay', ''), ','),
            'store_id' => $requestParams['store_id'] ?? '',
            'specified_channel' => $requestParams['specified_channel'] ?? 'pcredit',
            'business_params' => $requestParams['business_params'] ?? '',
            'ext_user_info' => $requestParams['ext_user_info'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

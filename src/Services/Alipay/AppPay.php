<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:06 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Services\Alipay;


use Paymenet\Services\AlipayBaseService;
use Payment\Consts\AlipayConst;
use Payment\Contracts\IRequestProxy;
use Payment\Exceptions\PaymentException;
use Payment\Helpers\ArrayHelper;

class AppPay extends AlipayBaseService implements IRequestProxy
{
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::APP_PAY_METHOD, $requestParams);

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
            'timeout_express' => $timeoutExp,
            'total_amount' => $requestParams['amount'] ?? '',
            'product_code' => $requestParams['product_code'] ?? '',
            'body' => $requestParams['body'] ?? '',
            'subject' => $requestParams['subject'] ?? '',
            'out_trade_no' => $requestParams['trade_no'] ?? '',
            'time_expire' => $timeExpire ? date('Y-m-d H:i', $timeExpire) : '',
            'goods_type' => $requestParams['goods_type'] ?? '',
            'promo_params' => $requestParams['promo_params'] ?? '',
            'passback_params' => urlencode($requestParams['return_params'] ?? ''),
            'extend_params' => $requestParams['extend_params'] ?? '',
            // 使用禁用列表
            //'enable_pay_channels' => '',
            'store_id' => $requestParams['store_id'] ?? '',
            'specified_channel' => $requestParams['specified_channel'] ?? 'pcredit', //支付宝原因，当前仅支持 pcredit
            'disable_pay_channels' => implode(self::$config->get('limit_pay', ''), ','),
            'ext_user_info' => $requestParams['ext_user_info'] ?? '',
            'business_params' => $requestParams['business_params'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}
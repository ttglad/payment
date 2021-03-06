<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:06 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\AlipayGlobal;


use Ttglad\Payment\Consts\AlipayGlobalConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Services\AlipayGlobalBaseService;

class WapPay extends AlipayGlobalBaseService implements IRequestContract
{
    protected $bizContentKey = [
        'out_trade_no',
        'total_fee',
        'subject',
        'currency',
        'refer_url',
        'product_code',
        'trade_information',
    ];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayGlobalConst::WAP_PAY_METHOD, $requestParams);
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
//            'appenv' => $requestParams['appenv'] ?? '',
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'subject' => $requestParams['subject'] ?? '',
            'body' => $requestParams['body'] ?? '',
//            'payment_type' => $requestParams['payment_type'] ?? '1',
//            'seller_id' => $requestParams['seller_id'] ?? '',
            'total_fee' => DataHelper::amountFormat($requestParams['amount']),
            'rmb_fee' => DataHelper::amountFormat($requestParams['rmb_fee']),
            'currency' => $requestParams['currency'] ?? 'USD',
            'timeout_rule' => $requestParams['timeout_rule'] ?? '',
            'order_create' => $requestParams['order_create'] ?? '',
//            'order_gmt_create' => $requestParams['order_gmt_create'] ?? '',
//            'order_valid_time' => $requestParams['order_valid_time'] ?? '',
            'supplier' => $requestParams['supplier'] ?? '',
            'secondary_merchant_id' => $requestParams['secondary_merchant_id'] ?? '',
            'secondary_merchant_name' => $requestParams['secondary_merchant_name'] ?? '',
            'secondary_merchant_industry' => $requestParams['secondary_merchant_industry'] ?? '',
            'refer_url' => $requestParams['refer_url'] ?? '',
            'product_code' => $requestParams['product_code'] ?? 'NEW_WAP_OVERSEAS_SELLER',
            'split_fund_info' => $requestParams['split_fund_info'] ?? '',
            'trade_information' => $requestParams['trade_information'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

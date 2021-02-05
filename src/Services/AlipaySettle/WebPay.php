<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/7 6:21 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\AlipaySettle;


use Ttglad\Payment\Consts\AlipaySettleConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Services\AlipaySettleBaseService;

class WebPay extends AlipaySettleBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_merge_no', 'order_details', 'timeout_express'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipaySettleConst::WEB_PAY_METHOD, $requestParams);
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
            'out_merge_no' => $requestParams['out_trade_no'] ?? '',
            'timeout_express' => $timeoutExp,
        ];

        $order_details = [];
        foreach ($requestParams['order_details'] as $_item) {
            $temp = [];
            $temp['app_id'] = self::$config->get('app_id', '');
            $temp['out_trade_no'] = $_item['out_trade_no'] ?? '';
            $temp['seller_id'] = $_item['seller_id'] ?? '';
            $temp['seller_logon_id'] = $_item['seller_logon_id'] ?? '';
            $temp['product_code'] = 'FAST_INSTANT_TRADE_PAY';
            $temp['total_amount'] = DataHelper::amountFormat($_item['amount']);
            $temp['subject'] = $_item['subject'] ?? '';
            $temp['body'] = $_item['body'] ?? '';
            $temp['show_url'] = $_item['show_url'] ?? '';
            $temp['passback_params'] = $_item['passback_params'] ?? '';

            $temp['sub_merchant']['merchant_id'] = $_item['merchant_id'] ?? '';
            $temp['settle_info']['settle_detail_infos'][] =
                [
                    'amount' => DataHelper::amountFormat($_item['amount']),
                    'trans_in_type' => $_item['trans_in_type'] ?? 'loginName',
                    'trans_in' => $_item['trans_in'],
                ];

            if (!empty($_item['goods_info'])) {
                $temp['goods_info'] = $this->formatGoodsInfo($_item['goods_info']);
            }

            $order_details[] = ArrayHelper::paramFilter($temp);
        }

        $bizContent['order_details'] = $order_details;

        return ArrayHelper::paramFilter($bizContent);
    }
}

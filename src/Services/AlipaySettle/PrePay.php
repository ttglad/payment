<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:06 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\AlipaySettle;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipaySettleBaseService;
use Ttglad\Payment\Consts\AlipaySettleConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;

class PrePay extends AlipaySettleBaseService implements IRequestContract
{
    protected $bizContentKey = [];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipaySettleConst::PRE_PAY_METHOD, $requestParams);
            $result = HttpHelper::post($this->gatewayUrl, $param, [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=' . $this->charset,
            ], 3.0, []);

            $result = json_decode($result, true);

            $content = $result['alipay_trade_merge_precreate_response'];

            $verifyResult = $this->verifySign($content, $result['sign']);
            if (!$verifyResult) {
                throw new PaymentException('verify error!', PaymentCode::SIGN_ERROR);
            }

            if ($content['code'] != self::REQ_SUCCESS) {
                $errorMessage = 'code is: ' . (isset($content['sub_code']) ? $content['sub_code'] : $content['code']) . ', message is: ' . (isset($content['sub_msg']) ? $content['sub_msg'] : $content['msg']);
                throw new PaymentException($errorMessage, PaymentCode::ALIPAY_SETTLE_RESULT_FAILED);
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
            $temp['product_code'] = $_item['product_code'] ?? 'QUICK_MSECURITY_PAY';
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

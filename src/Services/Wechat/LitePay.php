<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 2:30 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Wechat;


use Ttglad\Payment\Consts\WechatConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Services\WechatBaseService;

class LitePay extends WechatBaseService implements IRequestContract
{
    protected $needsKey = ['appid', 'mch_id', 'nonce_str', 'sign', 'body', 'out_trade_no', 'total_fee',
        'spbill_create_ip', 'notify_url', 'trade_type', 'openid'];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $ret = $this->requestXml(WechatConst::LITE_PAY_METHOD, $requestParams);
        } catch (PaymentException $e) {
            throw $e;
        }

        return $ret;
    }


    /**
     * @param array $requestParams
     * @return array|mixed
     */
    public function getSelfParams(array $requestParams)
    {
        $nowTime = isset($requestParams['time_start']) ? strtotime($requestParams['time_start']) : time();
        if (isset($requestParams['time_expire'])) {
            $timeExpire = date('YmdHis', $requestParams['time_expire']);
        } else {
            $timeExpire = date('YmdHis', $nowTime + 7200);
        }

        $selfParams = [
            'device_info' => $requestParams['device_info'] ?? '',
            'body' => $requestParams['body'] ?? '',
            'detail' => $requestParams['detail'] ?? '',
            'attach' => $requestParams['attach'] ?? '',
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'fee_type' => $requestParams['fee_type'] ?? 'CNY',
            'total_fee' => $requestParams['amount'] ?? '',
            'spbill_create_ip' => $requestParams['spbill_create_ip'] ?? '',
            'time_start' => $requestParams['time_start'] ?? '',
            'time_expire' => $timeExpire,
            'goods_tag' => $requestParams['goods_tag'] ?? '',
            'notify_url' => self::$config->get('notify_url', ''),
            'trade_type' => 'JSAPI',
            'product_id' => $requestParams['product_id'] ?? '',
            'limit_pay' => $requestParams['limit_pay'] ?? '',
            'openid' => $requestParams['openid'] ?? '',
            'receipt' => $requestParams['receipt'] ?? 'Y',
            'scene_info' => $requestParams['scene_info'] ?? '',
        ];

        return $selfParams;
    }

}

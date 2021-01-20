<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 3:45 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Wechat;


use Ttglad\Payment\Consts\WechatConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Services\WechatBaseService;

class TradeRefund extends WechatBaseService implements IRequestContract
{
    protected $needsKey = [
        'appid',
        'mch_id',
        'nonce_str',
        'sign',
        'out_trade_no',
        'out_refund_no',
        'total_fee',
        'refund_fee'
    ];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {

            $this->setRequestOptions([
                'cert' => self::$config->get('app_cert_pem', ''),
                'ssl_key' => self::$config->get('ssl_key', ''),
//                'verify' => self::$config->get('cert_path', ''),
            ]);

            $ret = $this->requestXml(WechatConst::TRADE_REFUND_METHOD, $requestParams);
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
        $selfParams = [
            'transaction_id' => $requestParams['transaction_id'] ?? '',
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'out_refund_no' => $requestParams['out_refund_no'] ?? '',
            'total_fee' => $requestParams['total_fee'] ?? '',
            'refund_fee' => $requestParams['refund_amount'] ?? '',
            'refund_fee_type' => $requestParams['refund_fee_type'] ?? 'CNY',
            'refund_desc' => $requestParams['refund_desc'] ?? '',
            'refund_account' => $requestParams['refund_account'] ?? 'REFUND_SOURCE_RECHARGE_FUNDS',
            'notify_url' => $requestParams['notify_url'] ?? '',
        ];

        return $selfParams;
    }
}

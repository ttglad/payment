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

class RefundQuery extends WechatBaseService implements IRequestContract
{
    protected $needsKey = ['appid', 'mch_id', 'nonce_str', 'sign', 'out_refund_no'];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $ret = $this->requestXml(WechatConst::REFUND_QUERY_METHOD, $requestParams);
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
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
            'transaction_id' => $requestParams['transaction_id'] ?? '',
            'out_refund_no' => $requestParams['out_refund_no'] ?? '',
            'refund_id' => $requestParams['refund_id'] ?? '',
            'offset' => $requestParams['offset'] ?? '',
        ];

        return $selfParams;
    }
}

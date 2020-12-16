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

class CancelOrder extends WechatBaseService implements IRequestContract
{
    protected $needsKey = ['appid', 'mch_id', 'nonce_str', 'sign', 'out_trade_no'];

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

            $ret = $this->requestXml(WechatConst::CANCEL_ORDER_METHOD, $requestParams);
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
        ];

        return $selfParams;
    }
}

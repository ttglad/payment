<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 10:52 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\WechatSettle;

use Ttglad\Payment\Consts\WechatSettleConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\WechatSettleBaseService;

class Cert extends WechatSettleBaseService implements IRequestContract
{
    protected $needsKey = [];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $url = sprintf($this->gatewayUrl, WechatSettleConst::CERT_METHOD);

            $data = HttpHelper::get($url, [], $this->getCurlHeader($url, '', 'GET'), 3.0);

            $cert = [];
            foreach ($data['data'] as $item) {
                $cert[$item['serial_no']] = $this->decryptToString($item['encrypt_certificate']['associated_data'], $item['encrypt_certificate']['nonce'], $item['encrypt_certificate']['ciphertext']);
            }

            return $cert;
        } catch (PaymentException $e) {
            throw $e;
        }

        return $data;
    }


    /**
     * @param array $requestParams
     * @return array|mixed
     */
    public function getSelfParams(array $requestParams)
    {
        return [];
    }
}

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


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\AlipayGlobalConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipayGlobalBaseService;

class TradeQuery extends AlipayGlobalBaseService implements IRequestContract
{
    protected $bizContentKey = [
        'out_trade_no',
    ];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayGlobalConst::TRADE_QUERY_METHOD, $requestParams);

            $result = HttpHelper::post($this->gatewayUrl, $param, [], 3.0, [
                'verify' => $this->caPath,
            ]);

            if ($result['is_success'] != 'T') {
                throw new PaymentException('result error', PaymentCode::ALIPAY_GLOBAL_RESULT_FAILED);
            }

            $data = $result['response']['trade'];
//            if ($data['trade_status'] != 'SUCCESS') {
//                throw new PaymentException('result error, code is: ' . $data['response_code'], PaymentCode::ALIPAY_GLOBAL_RESULT_FAILED);
//            }

            $this->verifySign($data, $result['sign']);

            return $data;
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

        $bizContent = [
            'out_trade_no' => $requestParams['out_trade_no'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

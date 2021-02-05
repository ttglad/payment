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
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipayGlobalBaseService;

class TradeRefund extends AlipayGlobalBaseService implements IRequestContract
{
    protected $bizContentKey = [
        'out_trade_no',
        'out_return_no',
        'return_rmb_amount',
        'product_code',
    ];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayGlobalConst::TRADE_REFUND_METHOD, $requestParams);

            $result = HttpHelper::post($this->gatewayUrl, $param, [], 3.0, [
                'verify' => $this->caPath,
            ]);

            if ($result['is_success'] != 'T') {
                throw new PaymentException('result error', PaymentCode::ALIPAY_GLOBAL_RESULT_FAILED);
            }

            return true;
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
            'out_return_no' => $requestParams['out_refund_no'] ?? '',
            'return_rmb_amount' => DataHelper::amountFormat($requestParams['refund_amount']),
            'return_amount' => DataHelper::amountFormat($requestParams['return_amount']),
            'currency' => $requestParams['currency'] ?? '',
            'gmt_return' => $requestParams['gmt_return'] ?? '',
            'reason' => $requestParams['reason'] ?? '',
            'product_code' => $requestParams['product_code'] ?? 'NEW_OVERSEAS_SELLER', // NEW_OVERSEAS_SELLER or NEW_WAP_OVERSEAS_SELLER
            'is_sync' => $requestParams['is_sync'] ?? 'N',
            'split_fund_info' => $requestParams['split_fund_info'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

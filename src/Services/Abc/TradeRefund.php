<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/14 2:36 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Abc;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\AbcConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Services\AbcBaseService;

class TradeRefund extends AbcBaseService implements IRequestContract
{
    protected $needsKey = [];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $response = $this->httpRequest($requestParams);


            $message = $this->getValue('Message', $response);
            $signature = $this->getValue('Signature', $response);

            $this->verifySign($message, $signature);

            $message = iconv('GBK', 'UTF-8', $message);
            $result = json_decode($message, true);
            $result = $result['TrxResponse'];
            if ($result['ReturnCode'] != self::REQ_SUCCESS) {
                throw new PaymentException($result['ErrorMessage'], PaymentCode::ABC_RESULT_FAILED);
            }

            $result = json_decode(iconv('GBK', 'UTF8', base64_decode($result['Order'])), true);

        } catch (PaymentException $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param array $requestParams
     * @return array|mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $selfParams = [
            'TrxType' => AbcConst::TRADE_REFUND_METHOD,
            'OrderDate' => $requestParams['orderDate'] ?? date('Y/m/d'),
            'OrderTime' => $requestParams['orderTime'] ?? date('H:i:s'),
            'MerRefundAccountNo' => $requestParams['merRefundAccountNo'] ?? '',
            'MerRefundAccountName' => $requestParams['merRefundAccountName'] ?? '',
            'OrderNo' => $requestParams['out_trade_no'],
            'NewOrderNo' => $requestParams['out_refund_no'],
            'CurrencyCode' => $requestParams['currencyCode'] ?? '156',
            'TrxAmount' => $requestParams['refund_amount'] / 100,
            'RefundType' => $requestParams['refundType'] ?? '0',
            'MerchantRemarks' => $requestParams['merchantRemarks'] ?? '',
            'MerRefundAccountFlag' => $requestParams['merRefundAccountFlag'] ?? '',
        ];

        $selfParams['SplitMerInfo'] = [];
        if (isset($requestParams['merchantsInfo']) && !empty($requestParams['merchantsInfo'])) {
            foreach ($requestParams['merchantsInfo'] as $merchant) {
                $selfParams['SplitMerInfo'][] = [
                    'SplitMerchantID' => $merchant['merchantId'],
                    'SplitAmount' => $merchant['amount'] / 100,
                ];
            }
        }

        return ArrayHelper::paramFilter($selfParams);
    }
}

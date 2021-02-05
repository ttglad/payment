<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 10:52 上午
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
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Services\AbcBaseService;

class AppPay extends AbcBaseService implements IRequestContract
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

            $response = $this->httpRequest($requestParams);

            $message = $this->getValue('Message', $response);
            $signature = $this->getValue('Signature', $response);

            $this->verifySign($message, $signature);

            $message = iconv('GBK', 'UTF-8', $message);
            $result = json_decode($message, true);

            if ($result['ReturnCode'] != self::REQ_SUCCESS) {
                throw new PaymentException($result['ErrorMessage'], PaymentCode::ABC_RESULT_FAILED);
            }

        } catch (PaymentException $e) {
            throw $e;
        }

        return $result;
    }


    /**
     * @param array $requestParams
     * @return array|mixed
     */
    public function getSelfParams(array $requestParams)
    {
        $nowTime = isset($requestParams['order_time']) ? $requestParams['order_time'] : time();
        if (isset($requestParams['order_expire'])) {
            $timeExpire = date('YmdHis', $requestParams['order_expire']);
        } else {
            $timeExpire = date('YmdHis', $nowTime + 7200);
        }

        $selfParams['request'] = [
            'TrxType' => AbcConst::APP_PAY_METHOD,
            'PaymentType' => $requestParams['paymentType'] ?? 'A',
            'PaymentLinkType' => $requestParams['paymentLinkType'] ?? '2',
            'UnionPayLinkType' => $requestParams['unionPayLinkType'] ?? '',
            'ReceiveAccount' => $requestParams['receiveAccount'] ?? '',
            'ReceiveAccName' => $requestParams['receiveAccName'] ?? '',
            'NotifyType' => $requestParams['notifyType'] ?? '1',
            'ResultNotifyURL' => self::$config->get('notify_url', ''),
            'IsBreakAccount' => $requestParams['isBreakAccount'] ?? '0',
            'MerchantRemarks' => $requestParams['remark'] ?? '',
            'ReceiveMark' => $requestParams['receiveMark'] ?? '',
            'ReceiveMerchantType' => $requestParams['receiveMerchantType'] ?? '',
            'SplitAccTemplate' => $requestParams['splitAccTemplate'] ?? '',
        ];

        $selfParams['order'] = [
            'PayTypeID' => $requestParams['payTypeID'] ?? 'ImmediatePay',
            'OrderDate' => date('Y/m/d'),
            'OrderTime' => date('H:i:s'),
            'orderTimeoutDate' => $timeExpire,
            'OrderNo' => $requestParams['out_trade_no'] ?? '',
            'CurrencyCode' => '156',
            'OrderAmount' => DataHelper::amountFormat($requestParams['amount']),
            'CommodityType' => $requestParams['commodityType'] ?? '0201',
            'ExpiredDate' => $requestParams['expiredDate'] ?? '',
            'Fee' => $requestParams['fee'] ?? '',
            'AccountNo' => $requestParams['accountNo'] ?? '',
            'OrderURL' => $requestParams['orderURL'] ?? '',
            'ReceiverAddress' => $requestParams['receiverAddress'] ?? '',
            'InstallmentMark' => $requestParams['installmentMark'] ?? '',
            'InstallmentCode' => $requestParams['installmentCode'] ?? '',
            'InstallmentNum' => $requestParams['installmentNum'] ?? '',
            'BuyIP' => $requestParams['buyIP'] ?? '',
            'OrderDesc' => $requestParams['orderDesc'] ?? '',
        ];

        $selfParams['orderItems'] = [];
        if (isset($requestParams['goodsInfo']) && !empty($requestParams['goodsInfo'])) {
            foreach ($requestParams['goodsInfo'] as $goods) {
                $selfParams['orderItems'][] = [
                    /**
                     * 增加商品明细
                     * 2021/1/18 add by TaoYl
                     */
                    'SubMerName' => $goods['subMerName'] ?? '',
                    'SubMerId' => $goods['subMerId'] ?? '',
                    'SubMerMCC' => $goods['subMerMCC'] ?? '',
                    'SubMerchantRemarks' => urlencode($goods['subMerchantRemarks'] ?? ''),
                    'ProductID' => $goods['productID'] ?? '',
                    'ProductName' => $goods['productName'] ?? '',
                    'UnitPrice' => $goods['unitPrice'] ?? '',
                    'Qty' => $goods['qty'] ?? '',
                    'ProductRemarks' => $goods['productRemarks'] ?? '',
                    'ProductType' => $goods['productType'] ?? '',
                    'ProductDiscount' => $goods['productDiscount'] ?? '',
                    'ProductExpiredDate' => $goods['productExpiredDate'] ?? '',
                ];
            }
        } else {
            $selfParams['orderItems'][] = [
                'ProductName' => '订单号:' . $requestParams['out_trade_no'],
            ];
        }

        $selfParams['splitAccInfoItems'] = [];
        if (isset($requestParams['merchantsInfo']) && !empty($requestParams['merchantsInfo'])) {
            foreach ($requestParams['merchantsInfo'] as $merchant) {
                $selfParams['SplitAccInfoItems'][] = [
                    'SplitMerchantID' => $merchant['merchantId'],
                    'SplitAmount' => $merchant['amount'] / 100,
                ];
            }
        }

        $params = ArrayHelper::paramFilter($selfParams['request']);
//        $params = array_map('urlencode', $params);
        $params['Order'] = ArrayHelper::paramFilter($selfParams['order']);
        $params['Order']['OrderItems'] = ArrayHelper::paramFilter($selfParams['orderItems']);
        $params['Order']['SplitAccInfoItems'] = ArrayHelper::paramFilter($selfParams['splitAccInfoItems']);

        return $params;
    }
}

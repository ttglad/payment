<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/14 2:36 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Union;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\UnionConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\UnionBaseService;

class TradeRefund extends UnionBaseService implements IRequestContract
{
    protected $needsKey = [
        'version',
        'encoding',
        'merId',
        'orderId',
        'bizType',
        'txnTime',
        'txnAmt',
        'txnType',
        'txnSubType',
        'accessType',
        'signature',
        'signMethod',
        'channelType'
    ];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {

            $url = sprintf($this->gatewayUrl, UnionConst::TRADE_REFUND_METHOD);
            $ret = HttpHelper::post($url, $this->buildParam($requestParams));

            if (empty($ret)) {
                throw new PaymentException('union request error', PaymentCode::UNION_TIMEOUT);
            }

            $result = ArrayHelper::coverStringToArray($ret);
            if (!isset($result['respCode'])) {
                throw new PaymentException('union request result error', PaymentCode::UNION_TIMEOUT);
            }

            print_r($result);

            if ($result['respCode'] != self::REQ_SUCCESS) {
                throw new PaymentException(sprintf('union request code error is %s', $result['respCode']), PaymentCode::UNION_RESULT_FAILED);
            }

            $this->verifySign($result);

            return $result;
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return array|mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $nowTime = isset($requestParams['order_time']) ? $requestParams['order_time'] : time();

        $selfParams = [
            'bizType' => $requestParams['biz_type'] ?? '000201',
            'txnAmt' => $requestParams['refund_amount'] ?? '',
            'txnTime' => date('YmdHis', $nowTime),
            'txnType' => $requestParams['txn_type'] ?? '04',
            'txnSubType' => $requestParams['txn_sub_type'] ?? '00',
            'accessType' => $requestParams['accessType'] ?? '0',
            'backUrl' => $requestParams['notify_url'] ?? '',
            'subMerId' => $requestParams['sub_mer_id'] ?? '',
            'subMerAbbr' => $requestParams['sub_mer_abbr'] ?? '',
            'subMerName' => $requestParams['sub_mer_name'] ?? '',
            'orderId' => $requestParams['out_trade_no'] ?? '',
            'acqInsCode' => $requestParams['acq_ins_code'] ?? '',
            'origQryId' => $requestParams['out_refund_no'] ?? '',
            'reserved' => $requestParams['reserved'] ?? '',
            'channelType' => $requestParams['reserved'] ?? '08',
            'accSplitData' => $requestParams['acc_split_data'] ?? '',
            'ctrlRule' => $requestParams['ctrl_rule'] ?? '',
            'reqReserved' => $requestParams['req_reserved'] ?? '',
            'termId' => $requestParams['term_id'] ?? '',
        ];

        return ArrayHelper::paramFilter($selfParams);
    }
}

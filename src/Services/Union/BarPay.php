<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/15 10:52 上午
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

class BarPay extends UnionBaseService implements IRequestContract
{
    protected $needsKey = ['qrNo', 'version', 'encoding', 'merId', 'orderId', 'bizType', 'txnTime', 'backUrl', 'currencyCode', 'txnAmt',
        'txnType', 'txnSubType', 'accessType', 'signature', 'signMethod', 'channelType'];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $url = sprintf($this->gatewayUrl, UnionConst::BAR_PAY_METHOD);

            print_r($this->buildParam($requestParams));
            $ret = HttpHelper::post($url, $this->buildParam($requestParams));

            print_r($ret);

            if (empty($ret)) {
                throw new PaymentException('union request error', PaymentCode::UNION_TIMEOUT);
            }

            $result = ArrayHelper::coverStringToArray($ret);
            if (!isset($result['respCode'])) {
                throw new PaymentException('union request result error', PaymentCode::UNION_TIMEOUT);
            }

            if ($result['respCode'] != self::REQ_SUCCESS) {
                throw new PaymentException('union request code error', PaymentCode::UNION_RESULT_FAILED);
            }

            $this->verifySign($result);
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

        $selfParams = [
            'qrNo' => $requestParams['qr_no'] ?? '',
            'bizType' => $requestParams['biz_type'] ?? '000201',
            'txnTime' => date('YmdHis', $nowTime),
            'backUrl' => self::$config->get('notify_url', ''),
            'txnAmt' => $requestParams['amount'] ?? '',
            'txnType' => $requestParams['txn_type'] ?? '01',
            'txnSubType' => $requestParams['txn_sub_type'] ?? '06',
            'accessType' => $requestParams['accessType'] ?? '0',
            'channelType' => $requestParams['channel_type'] ?? '08',
            'orderId' => $requestParams['out_trade_no'] ?? '',
            'termInfo' => $requestParams['term_info'] ?? '',
            'accInsCode' => $requestParams['acc_ins_code'] ?? '',
            'reserved' => $requestParams['reserved'] ?? '',
            'accSplitData' => $requestParams['acc_split_data'] ?? '',
            'riskRateInfo' => $requestParams['risk_rate_info'] ?? '',
            'ctrlRule' => $requestParams['ctrl_rule'] ?? '',
            'customerInfo' => $requestParams['customer_info'] ?? '',
            'acqAddnData' => $requestParams['acq_addn_data'] ?? '',
            'termId' => $requestParams['term_id'] ?? '',
        ];

        return ArrayHelper::paramFilter($selfParams);
    }
}

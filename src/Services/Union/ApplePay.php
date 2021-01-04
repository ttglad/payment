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

class ApplePay extends UnionBaseService implements IRequestContract
{
    protected $needsKey = ['version', 'encoding', 'merId', 'orderId', 'bizType', 'txnTime', 'backUrl', 'currencyCode', 'txnAmt',
        'txnType', 'txnSubType', 'accessType', 'signature', 'signMethod', 'channelType'];

    /**
     * @param array $requestParams
     * @return array|false|mixed
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $url = sprintf($this->gatewayUrl, UnionConst::APP_PAY_METHOD);
            $ret = HttpHelper::post($url, $this->buildParam($requestParams));

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
            'bizType' => $requestParams['biz_type'] ?? '000802',
            'txnTime' => date('YmdHis', $nowTime),
            'backUrl' => self::$config->get('notify_url', ''),
            'txnAmt' => $requestParams['amount'] ?? '',
            'txnType' => $requestParams['txn_type'] ?? '01',
            'txnSubType' => $requestParams['txn_sub_type'] ?? '01',
            'accessType' => $requestParams['accessType'] ?? '0',
            'channelType' => $requestParams['channel_type'] ?? '08',
            'orderId' => $requestParams['out_trade_no'] ?? '',
            'orderDesc' => $requestParams['body'] ?? '',
            'merAbbr' => $requestParams['mer_abbr'] ?? '',
            'merCatCode' => $requestParams['mer_cat_code'] ?? '',
            'merName' => $requestParams['mer_name'] ?? '',
            'customerInfo' => $requestParams['customer_info'] ?? '',
            'cardTransData' => $requestParams['card_trans_data'] ?? '',
            'acqInsCode' => $requestParams['acq_ins_code'] ?? '',
            'instalTransInfo' => $requestParams['instal_trans_info'] ?? '',
            'accNo' => $requestParams['acc_no'] ?? '',
            'reserved' => $requestParams['reserved'] ?? '',
            'accSplitData' => $requestParams['acc_split_data'] ?? '',
            'riskRateInfo' => $requestParams['risk_rate_info'] ?? '',
            'ctrlRule' => $requestParams['ctrl_rule'] ?? '',
            'reqReserved' => $requestParams['req_reserved'] ?? '',
            'termId' => $requestParams['term_id'] ?? '',
        ];

        return ArrayHelper::paramFilter($selfParams);
    }
}

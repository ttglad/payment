<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/10 10:12 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\Alipay;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Consts\AlipayConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipayBaseService;

class TradeRefund extends AlipayBaseService implements IRequestContract
{
    protected $bizContentKey = ['out_trade_no', 'refund_amount'];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipayConst::TRADE_REFUND_METHOD, $requestParams);

            $ret = HttpHelper::get($this->gatewayUrl, $param, [], 3);

            $retArray = json_decode($ret, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PaymentException(sprintf('format trade create get error, [%s]', json_last_error_msg()), PaymentCode::JSON_FORMAT_ERROR, ['raw' => $ret]);
            }

            $content = $retArray['alipay_trade_refund_response'];
            if ($content['code'] !== self::REQ_SUCCESS) {
                throw new PaymentException(sprintf('request get failed, msg[%s], sub_msg[%s]', $content['msg'], $content['sub_msg']), PaymentCode::SIGN_ERROR, $content);
            }
            $signFlag = $this->verifySign($content, $retArray['sign']);
            if (!$signFlag) {
                throw new PaymentException('check sign failed', PaymentCode::SIGN_ERROR, $retArray);
            }
            return $content;
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
            'trade_no' => $requestParams['trade_no'] ?? '',
            'refund_amount' => $requestParams['refund_amount'] > 0 ? number_format($requestParams['refund_amount'] / 100, 2) : '',
            'refund_currency' => $requestParams['refund_currency'] ?? 'CNY',
            'refund_reason' => $requestParams['refund_reason'] ?? '',
            'out_request_no' => $requestParams['out_request_no'] ?? '',
            'operator_id' => $requestParams['operator_id'] ?? '',
            'store_id' => $requestParams['store_id'] ?? '',
            'terminal_id' => $requestParams['terminal_id'] ?? '',
            'goods_detail' => $requestParams['goods_detail'] ?? '',
            'refund_royalty_parameters' => $requestParams['refund_royalty_parameters'] ?? '',
            'org_pid' => $requestParams['org_pid'] ?? '',
            'query_options' => $requestParams['query_options'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

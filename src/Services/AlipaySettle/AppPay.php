<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:06 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Services\AlipaySettle;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Helpers\DataHelper;
use Ttglad\Payment\Helpers\HttpHelper;
use Ttglad\Payment\Services\AlipaySettleBaseService;
use Ttglad\Payment\Consts\AlipaySettleConst;
use Ttglad\Payment\Contracts\IRequestContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Helpers\ArrayHelper;

class AppPay extends AlipaySettleBaseService implements IRequestContract
{
    protected $bizContentKey = [];

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function request(array $requestParams)
    {
        try {
            $param = $this->buildParam(AlipaySettleConst::APP_PAY_METHOD, $requestParams);
            return http_build_query($param);
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
            'pre_order_no' => $requestParams['pre_order_no'] ?? '',
        ];

        return ArrayHelper::paramFilter($bizContent);
    }
}

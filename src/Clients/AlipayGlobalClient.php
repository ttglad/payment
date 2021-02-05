<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 4:28 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Clients;


use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Contracts\IPayContract;
use Ttglad\Payment\Exceptions\PaymentException;
use Ttglad\Payment\Services\AlipayGlobal\RefundQuery;
use Ttglad\Payment\Services\AlipayGlobal\TradeQuery;
use Ttglad\Payment\Services\AlipayGlobal\TradeRefund;


class AlipayGlobalClient extends Client implements IPayContract
{
    /**
     * @param string $channel
     * @param array $requestParams
     * @return mixed
     * @throws PaymentException
     */
    public function pay(string $channel, array $requestParams)
    {
        $class = ucfirst(str_replace(['-', '_', ''], '', strtolower($channel)));
        $className = "Ttglad\\Payment\\Services\\AlipayGlobal\\{$class}Pay";

        if (!class_exists($className)) {
            throw new PaymentException(sprintf('class [%s] not exists.', $className), PaymentCode::CLASS_NOT_EXIST);
        }

        try {
            $charge = new $className();
            return $charge->request($requestParams);
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function refund(array $requestParams)
    {
        try {
            $charge = new TradeRefund();
            return $charge->request($requestParams);
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function cancel(array $requestParams)
    {

    }

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function close(array $requestParams)
    {

    }

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function tradeQuery(array $requestParams)
    {
        try {
            $charge = new TradeQuery();
            return $charge->request($requestParams);
        } catch (PaymentException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed|string
     * @throws PaymentException
     */
    public function refundQuery(array $requestParams)
    {
        try {
            $charge = new RefundQuery();
            return $charge->request($requestParams);
        } catch (PaymentException $e) {
            throw $e;
        }
    }
}

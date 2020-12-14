<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 5:28 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Contracts;


interface IPayContract
{
    /**
     * 支付操作
     * @param string $channel
     * @param array $requestParams
     * @return mixed
     */
    public function pay(string $channel, array $requestParams);

    /**
     * 退款操作
     * @param array $requestParams
     * @return mixed
     */
    public function refund(array $requestParams);

    /**
     * 取消交易
     * @param array $requestParams
     * @return mixed
     */
    public function cancel(array $requestParams);

    /**
     * 关闭交易
     * @param array $requestParams
     * @return mixed
     */
    public function close(array $requestParams);

    /**
     * 交易查询
     * @param array $requestParams
     * @return mixed
     */
    public function tradeQuery(array $requestParams);

    /**
     * 退款查询
     * @param array $requestParams
     * @return mixed
     */
    public function refundQuery(array $requestParams);

}

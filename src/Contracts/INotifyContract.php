<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 5:34 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Contracts;


interface INotifyContract
{

    /**
     * 异步通知
     * @param IPayNotify $callback
     * @return mixed
     */
    public function payNotify(IPayNotify $callback);

    /**
     * 异步通知
     * @param IPayNotify $callback
     * @return mixed
     */
    public function refundNotify(IPayNotify $callback);
}

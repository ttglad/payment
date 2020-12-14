<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 5:32 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Contracts;


interface ITransferContract
{

    /**
     * 转账
     * @param array $requestParams
     * @return mixed
     */
    public function transfer(array $requestParams);

    /**
     * 转账查询
     * @param array $requestParams
     * @return mixed
     */
    public function transferQuery(array $requestParams);
}

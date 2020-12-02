<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:25 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Contracts;


interface IRequestProxy
{
    /**
     * @param array $requestParams
     * @return mixed
     */
    public function request(array $requestParams);
}
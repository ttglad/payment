<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 5:33 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Contracts;

interface IBillContract
{

    /**
     * @param array $requestParams
     * @return mixed
     */
    public function billDownload(array $requestParams);

    /**
     * @param array $requestParams
     * @return mixed
     */
    public function settleDownload(array $requestParams);
}

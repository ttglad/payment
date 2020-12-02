<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 4:06 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Exceptions;


class PaymentException extends \Exception
{

    private $info = '';

    /**
     * PaymentException constructor.
     * @param string $message
     * @param int $code
     * @param array $info
     */
    public function __construct(string $message, int $code, $info = [])
    {
        parent::__construct($message, $code);

        $this->info = $info;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info();
    }
}
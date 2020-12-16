<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 3:59 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Codes;

final class PaymentCode
{
    const PARAM_ERROR = 100001; // 参数错误
    const CONFIG_ERROR = 10002; // 配置错误
    const CLASS_NOT_EXIST = 100003; // 不存在的类

    const JSON_FORMAT_ERROR = 101001;
    const XML_FORMAT_ERROR = 101002;

    const SIGN_ERROR = 102001;

    // wechat error
    const WECHAT_TIMEOUT = 900001;
    const WECHAT_CHECK_FAILED = 900002;

    // alipay error
    const ALIPAY_TIMEOUT = 910001;
}

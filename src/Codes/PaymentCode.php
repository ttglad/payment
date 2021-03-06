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
    const METHOD_NOT_EXIST = 100004; // 不存在的方法

    const JSON_FORMAT_ERROR = 101001;
    const XML_FORMAT_ERROR = 101002;

    const SIGN_ERROR = 102001;
    const VERIFY_ERROR = 102002;

    // wechat error
    const WECHAT_TIMEOUT = 900001;
    const WECHAT_CHECK_FAILED = 900002;

    // alipay error
    const ALIPAY_TIMEOUT = 910001;

    // union error
    const UNION_TIMEOUT = 920001;
    const UNION_CHECK_FAILED = 920002;
    const UNION_RESULT_FAILED = 920002;

    // abc error
    const ABC_TIMEOUT = 930001;
    const ABC_CHECK_FAILED = 930002;
    const ABC_RESULT_FAILED = 930003;

    // alipay global error
    const ALIPAY_GLOBAL_TIMEOUT = 940001;
    const ALIPAY_GLOBAL_CHECK_FAILED = 940002;
    const ALIPAY_GLOBAL_RESULT_FAILED = 940003;

    // alipay settle error
    const ALIPAY_SETTLE_TIMEOUT = 950001;
    const ALIPAY_SETTLE_CHECK_FAILED = 950002;
    const ALIPAY_SETTLE_RESULT_FAILED = 950003;
}

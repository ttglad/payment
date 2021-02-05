<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 9:15 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Consts;


final class AlipayGlobalConst
{
    const APP_PAY_METHOD = 'mobile.securitypay.pay';
    const WAP_PAY_METHOD = 'create_forex_trade_wap';
    const WEB_PAY_METHOD = 'create_forex_trade';
    const TRADE_QUERY_METHOD = 'single_trade_query';
    const TRADE_REFUND_METHOD = 'forex_refund';
    const REFUND_QUERY_METHOD = 'alipay.acquire.refund.query';
}

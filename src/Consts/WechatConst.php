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


final class WechatConst
{
    const APP_PAY_METHOD = 'pay/unifiedorder';
    const LITE_PAY_METHOD = 'pay/unifiedorder';
    const WAP_PAY_METHOD = 'pay/unifiedorder';
    const QR_PAY_METHOD = 'pay/unifiedorder';
    const BAR_PAY_METHOD = 'pay/micropay';
    const TRADE_QUERY_METHOD = 'pay/orderquery';
    const CANCEL_ORDER_METHOD = 'secapi/pay/reverse';
    const CLOSE_ORDER_METHOD = 'pay/closeorder';
//    const TRADE_REFUND_METHOD = 'secapi/pay/refund';
    const TRADE_REFUND_METHOD = 'secapi/pay/refundv2';
//    const REFUND_QUERY_METHOD = 'pay/refundquery';
    const REFUND_QUERY_METHOD = 'pay/refundqueryv2';
}

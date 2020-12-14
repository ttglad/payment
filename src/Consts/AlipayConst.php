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


final class AlipayConst
{
    const APP_PAY_METHOD = 'alipay.trade.app.pay';
    const WAP_PAY_METHOD = 'alipay.trade.wap.pay';
    const WEP_PAY_METHOD = 'alipay.trade.page.pay';
    const TRADE_PAY_METHOD = 'alipay.trade.create';
    const BAR_PAY_METHOD = 'alipay.trade.pay';
    const QR_PAY_METHOD = 'alipay.trade.precreate';
    const TRADE_QUERY_METHOD = 'alipay.trade.query';
    const CANCEL_ORDER_METHOD = 'alipay.trade.cancel';
    const CLOSE_ORDER_METHOD = 'alipay.trade.close';
    const TRADE_REFUND_METHOD = 'alipay.trade.refund';
    const REFUND_QUERY_METHOD = 'alipay.trade.fastpay.refund.query';
}

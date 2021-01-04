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


final class UnionConst
{
    const APP_PAY_METHOD = 'gateway/api/appTransReq.do';
    const WAP_PAY_METHOD = 'gateway/api/frontTransReq.do';
    const WEB_PAY_METHOD = 'gateway/api/frontTransReq.do';
    const QR_PAY_METHOD = 'gateway/api/backTransReq.do';
    const BAR_PAY_METHOD = 'gateway/api/backTransReq.do';
    const TRADE_QUERY_METHOD = 'gateway/api/queryTrans.do';
    const CANCEL_ORDER_METHOD = 'gateway/api/backTransReq.do';
    const TRADE_REFUND_METHOD = 'gateway/api/backTransReq.do';
    const REFUND_QUERY_METHOD = 'gateway/api/queryTrans.do';
}

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


final class WechatSettleConst
{
    const CERT_METHOD = 'v3/certificates';
    const APP_PAY_METHOD = 'v3/combine-transactions/app';
    const LITE_PAY_METHOD = 'v3/combine-transactions/jsapi';
    const WAP_PAY_METHOD = 'v3/combine-transactions/h5';
    const QR_PAY_METHOD = 'v3/combine-transactions/native';
    const TRADE_QUERY_METHOD = 'v3/combine-transactions/out-trade-no/%s';
    const CLOSE_ORDER_METHOD = 'v3/combine-transactions/out-trade-no/%s/close';
    const TRADE_REFUND_METHOD = 'v3/ecommerce/refunds/apply';
    const REFUND_QUERY_METHOD = 'v3/ecommerce/refunds/out-refund-no/%s';
}

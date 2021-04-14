# payment
目前仅支持支付宝、支付宝全球购、支付宝直付通、微信、微信合单支付、银联、农行（各端支付、退款、支付查询、退款查询、关闭订单、撤销订单）。

# 安装方法
```
composer require ttglad/payment
```

# 使用方法
###### 通用参数说明：
###### amount（`金额：单位分`）
###### out_trade_no（`单号`）
###### refund_amount（`退款金额：单位分`）
###### out_refund_no（`退款单号`）
###### goods_info(`商品信息`)
```
$goods_info = [
    [
        'goods_id' = '', // 商品id 必须
        'goods_name' = '', // 商品名称 必须
        'goods_quantity' = '', // 商品数量 必须
        'goods_price' = '', // 商品单价 单位分 必须
        'goods_id_third' = '', // 三方商品id
        'goods_category' = '', // 商品品类
        'goods_categories_tree' = '', // 商品品类树
        'goods_body' = '', // 商品描述信息
        'goods_url' = '', // // 商品展示url
    ],
    [],[],
];
```

## 使用方法

```
# 农行 => \Ttglad\Payment\PaymentProxy::ABC
# 支付宝 => \Ttglad\Payment\PaymentProxy::ALIPAY
# 支付宝国际版 => \Ttglad\Payment\PaymentProxy::ALIPAY_GLOBAL
# 支付宝分账 => \Ttglad\Payment\PaymentProxy::ALIPAY_SETTLE
# 银联 => \Ttglad\Payment\PaymentProxy::UNION
# 微信 => \Ttglad\Payment\PaymentProxy::WECHAT
# 微信分账 => \Ttglad\Payment\PaymentProxy::WECHAT_SETTLE

$client = new \Ttglad\Payment\PaymentProxy(\Ttglad\Payment\PaymentProxy::ALIPAY, [
    'app_id' => '',
    'ali_public_key' => '',
    'ali_private_key' => '',
    'key_type' => '', // normal普通公钥，cert证书公钥
    'app_cert_path' => '', //key_type == cert 时需要
    'root_cert_path' => '', //key_type == cert 时需要
    'sign_type' => 'RSA2', // RSA,RSA2
    'notify_url' => '',
    'gateway_url' => 'https://openapi.alipay.com/gateway.do',
]);

# 支付 
$result = $client->pay('app', [
    'body' => 'ali app pay',
    'subject' => '测试支付宝APP支付',
    'out_trade_no' => time() . rand(10000000, 99999999),
    'time_expire' => time() + 6000, // 表示必须 600s 内付款
    'amount' => 1, // 单位为分，最小1分
    'store_id' => '8000',
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

# 以下是按照支付方式分开使用
## 支付宝

```
$client = new \Ttglad\Payment\Clients\AlipayClient();
$client->setConfig([
    'app_id' => '',
    'ali_public_key' => '',
    'ali_private_key' => '',
    'key_type' => '', // normal普通公钥，cert证书公钥
    'app_cert_path' => '', //key_type == cert 时需要
    'root_cert_path' => '', //key_type == cert 时需要
    'sign_type' => 'RSA2', // RSA,RSA2
    'notify_url' => '',
    'gateway_url' => 'https://openapi.alipay.com/gateway.do',
]);

# 支付 
$result = $client->pay('app', [
    'body' => 'ali app pay',
    'subject' => '测试支付宝APP支付',
    'out_trade_no' => time() . rand(10000000, 99999999),
    'time_expire' => time() + 6000, // 表示必须 600s 内付款
    'amount' => 1, // 单位为分，最小1分
    'store_id' => '8000',
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

## 支付宝全球购

```
$client = new \Ttglad\Payment\Clients\AlipayGlobalClient();
$client->setConfig([
    'app_id' => '',
    'ali_public_key' => '',
    'ali_private_key' => '',
    'ca_path' => '', // 
    'sign_type' => 'RSA', // RSA,MD5
    'notify_url' => '',
    'gateway_url' => 'https://intlmapi.alipay.com/gateway.do',
]);

# 支付 
$result = $client->pay('app', [
    'body' => 'ali app pay',
    'subject' => '测试支付宝APP支付',
    'out_trade_no' => time() . rand(10000000, 99999999),
    'time_expire' => time() + 6000, // 表示必须 600s 内付款
    'amount' => 1, // 单位为分，最小1分
    'seller_id' => '123123',
    'refer_url' => '123123',
    'trade_information' => '123',
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

## 支付宝分账（直付通）

```
$client = new \Ttglad\Payment\Clients\AlipaySettleClient();
$client->setConfig([
    'app_id' => '',
    'ali_public_key' => '',
    'ali_private_key' => '',
    'key_type' => '', // normal普通公钥，cert证书公钥
    'app_cert_path' => '', //key_type == cert 时需要
    'root_cert_path' => '', //key_type == cert 时需要
    'sign_type' => 'RSA2', // RSA,RSA2
    'notify_url' => '',
    'gateway_url' => 'https://openapi.alipay.com/gateway.do',
]);

# 支付 
$prePay = $client->pay('pre', [
    'out_trade_no' => '',
    'time_expire' => '',
    'order_details' => [
        [
            'out_trade_no' => '', // 子单号
            'amount' => '', // 金额，单位分
            'subject' => '', // 标题
            'merchant_id' => '', // 子商户号
            'trans_in' => '' // 子商户收款账户,
            'product_code' => 'QUICK_MSECURITY_PAY' // app:QUICK_MSECURITY_PAY wap:QUICK_WAP_WAY,
        ],[],[]
    ],
]);
$pay = $alipay->pay('app', [
    'pre_order_no' => $prePay['pre_order_no'],
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

## 微信

```
$client = new \Ttglad\Payment\Clients\WechatClient();
$client->setConfig([
   'app_id' => '', 
   'mch_id' => '',
   'sub_appid' => '',
   'sub_mch_id' => '',
   'merchant_key' => '', // 商户号秘钥
   'sign_type' => '', // MD5、HMAC-SHA256
   'notify_url' => '', // 通知地址
   'app_cert_pem' => '', // 退款需要
   'ssl_key' => '', // 退款需要
]);

# 支付 
$result = $client->pay('app', [
    'body' => 'wx app pay',
    'out_trade_no' => time() . rand(10000000, 99999999),
    'amount' => 1,
    'spbill_create_ip' => '127.0.0.1',
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

## 微信分账（合单支付）

```
$client = new \Ttglad\Payment\Clients\WechatSettleClient();
$config = [
    'app_id' => '',
    'merchant_id' => '',
    'merchant_key' => '',
    'private_key' => '',
    'cert_key' => '',
    'notify_url' => '',
];
$client->setConfig($config);
// 需要获取证书，证书为数组，证书可以使用缓存
$config['certs'] = $client->cert();
$client->setConfig($config);
$pay = $client->pay('app', [
    'out_trade_no' => time() . rand(10000000, 99999999),
    'sub_orders' => [
        [
            'mchid' => '',
            'attach' => 'attach',
            'amount' => [
                'total_amount' => 100,
                'currency' => 'CNY',
            ],
            'out_trade_no' => time() . rand(10000000, 99999999),
            'sub_mchid' => '',
            'description' => '商品描述',
            'profit_sharing' => true,
        ],
    ],
    'notify_url' => 'http://test.com',
]);

```

## 银联

```
$client = new \Ttglad\Payment\Clients\UnionClient();
$client->setConfig([
    'version' => '5.1.0',
    'mer_id' => '', // 商户号
    'cert_dir' => '', // 证书目录
    'cert_path' => '', // 证书地址
    'cert_pwd' => '', // 证书密码
    'notify_url' => '', // 通知地址
]);

# 支付 
$result = $client->pay('app', [
    'body' => 'union app pay',
    'out_trade_no' => time() . rand(10000000, 99999999),
    'amount' => 1,
    'default_pay_type' => '0001',
    'req_reserved' => '123123123',
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
    'amount' => 9480,
    'order_time' => strtotime('2020-12-20 23:59:36'),
]);
```

# 农行
```
$client = new \Ttglad\Payment\Clients\AbcClient();
$client->setConfig([
    'version' => 'V3.0.0',
    'merchant_id' => '', // 商户号
    'plat_cert_path' => '', // 证书目录
    'cert_path' => '', // 证书地址
    'cert_pwd' => '', // 证书密码
    'notify_url' => '', // 通知地址
]);

# 支付 
$result = $client->pay('app', [
    'out_trade_no' => time() . rand(10000000, 99999999),
    'amount' => 1,
]);

# 查询
$result = $client->tradeQuery([
    'out_trade_no' => 'test1000307171',
]);
```

# 第三方文档
#### [支付宝](https://opendocs.alipay.com/apis)
#### [支付宝全球购](https://global.alipay.com/docs/ac/global)
#### [支付宝直付通](https://www.yuque.com/docs/share/88bcc6ea-4f95-4917-95f4-6952b3a45636)
#### [微信](https://pay.weixin.qq.com/wiki/doc/api/index.html)
#### [微信合单支付](https://pay.weixin.qq.com/wiki/doc/apiv3/wxpay/pages/combine.shtml)
#### [银联](https://open.unionpay.com/tjweb/api/dictionary)
#### [农行]()(目前无在线文档)

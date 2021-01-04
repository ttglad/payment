# payment
目前仅支持支付宝、微信（各端支付、退款、支付查询、退款查询、关闭订单、撤销订单）。

# 安装方法
```
composer require ttglad/payment
```

# 使用方法
通用参数说明：amount（`金额：单位分`）

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

# 第三方文档
#### [支付宝](https://opendocs.alipay.com/apis)
#### [微信](https://pay.weixin.qq.com/wiki/doc/api/index.html)
#### [银联](https://open.unionpay.com/tjweb/api/dictionary)

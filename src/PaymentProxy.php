<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2021/2/5 10:54 上午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/12/2 4:28 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment;

use Ttglad\Payment\Codes\PaymentCode;
use Ttglad\Payment\Exceptions\PaymentException;

class PaymentProxy
{
    const ABC = 'Abc';
    const ALIPAY = 'Alipay';
    const ALIPAY_GLOBAL = 'AlipayGlobal';
    const ALIPAY_SETTLE = 'AlipaySettle';
    const UNION = 'Union';
    const WECHAT = 'Wechat';
    const WECHAT_SETTLE = 'WechatSettle';


    protected $proxy = null;

    /**
     * Payment constructor.
     * @param string $proxy
     * @param array $config
     * @throws PaymentException
     */
    public function __construct(string $proxy, array $config)
    {

        $name = ucfirst(str_replace(['-', '_', ''], '', $proxy));
        $className = "Ttglad\\Payment\\Clients\\{$name}Client";

        if (!class_exists($className)) {
            throw new PaymentException(sprintf('class [%s] not exists.', $className), PaymentCode::CLASS_NOT_EXIST);
        }

        $this->proxy = new $className();
        $this->proxy->setConfig($config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws PaymentException
     * @throws \ReflectionException
     */
    public function __call($name, $arguments)
    {
        // 获取函数参数信息
        try {
            if (!method_exists($this->proxy, $name)) {
                throw new PaymentException(sprintf('[%s] method is not exist in proxy [%s].', $name,
                    $this->proxy->className()), PaymentCode::METHOD_NOT_EXIST);
            }

            $reflect = new \ReflectionMethod($this->proxy, $name);
            $params = $reflect->getParameters();
            $countParams = count($params);
            if ($countParams !== count($arguments)) {
                throw new PaymentException(sprintf('[%s] method need [%d] params.', $name, $countParams),
                    PaymentCode::PARAM_ERROR);
            }
        } catch (\ReflectionException $e) {
            throw new PaymentException(sprintf('[%s] class not found.', $this->proxy->className()),
                PaymentCode::CLASS_NOT_EXIST);
        } catch (PaymentException $e) {
            throw $e;
        }

        try {
            return call_user_func_array([$this->proxy, $name], $arguments);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

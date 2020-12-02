<?php

/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 3:46 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Paymenet\Services;

/**
 * Class BaseService
 * @package Paymenet\Services
 */
abstract class BaseService
{
    const VERSION = '0.0.1';

    /**
     * @var Config
     */
    public static $config = null;

    /**
     * 获取版本号
     * @return string
     */
    public static function getVersion()
    {
        return self::VERSION;
    }

    /**
     * 获取类名称
     * @return string
     */
    public function className()
    {
        return get_called_class();
    }

    /**
     * 设置配置文件
     * @param array $config
     */
    public function setConfig(array $config)
    {
        self::$config = new Config($config);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: taoYl
 * Date: 2020/11/26 3:46 下午
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ttglad\Payment\Configs;


class Config implements \ArrayAccess
{
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        $config = $this->config;

        if (is_null($key)) {
            return null;
        }

        if (isset($config[$key])) {
            return $config[$key];
        }

        return $default;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->config);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (isset($this->config[$offset])) {
            $this->config[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (isset($this->config[$offset])) {
            unset($this->config[$offset]);
        }
    }
}

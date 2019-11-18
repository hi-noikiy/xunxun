<?php
namespace Common\Util;

use Think\Log;
/**
 * Created by PhpStorm.
 * User: shellvon
 * Date: 16/4/7
 * Time: 下午2:00.
 */
class TokenHelper
{
    const MAX_UID_SIZE = 256;
    const MAX_TOKEN_SIZE = 1024;

    private static $instance = null;
    private static $config;

    private $expired_time;
    private $redis_obj;

    //单例.
    protected function __construct()
    {
        if (isset(static::$config['expired_time'])) {
            $this->expired_time = self::$config['expired_time'];
        } else {
            $this->expired_time = 60 * 60 * 24 * 30;
        }
        if (!isset(static::$config['mem_server'])) {
            $host = '127.0.0.1';
            $port = '6379';
        } else {
            list($host, $port) = explode(':', self::$config['mem_server']);
        }
        $this->redis_obj = new Redis();
        $this->redis_obj->addServer($host, $port);
    }

    private function __clone()
    {
    }

    // 防止序列化的时候被***
    private function __wakeup()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 配置.
     *
     * @param null $config
     *
     * @return mixed
     */
    public static function config($config = null)
    {
        if (empty($config)) {
            return static::$config;
        }
        static::$config = $config;
    }

    /***
     * @param string $key
     * @param mixed $data
     * @param integer $expired_time
     *
     * @return bool
     */
    public function set($key, $data, $expired_time = null)
    {
        if ($expired_time === null) {
            $expired_time = $this->expired_time;
        }

        return $this->redis_obj->set($key, $data, $expired_time);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->redis_obj->get($key);
    }

    /**
     * @param $key
     */
    public function delete($key)
    {
        $this->redis_obj->delete($key);
    }

    /**
     * 随机生成token.
     *
     * @param $salt
     *
     * @return string
     */
    public function generateToken($salt)
    {
        return md5(md5($this->generateRandomString(10)).$salt);
    }

    /**
     * 生成指定长度的随机字符串.
     *
     * @param int $length
     *
     * @return string
     */
    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_len = strlen($characters);
        $random_str = '';
        for ($i = 0; $i < $length; ++$i) {
            $random_str .= $characters[rand(0, $characters_len - 1)];
        }

        return $random_str;
    }

    /**
     * 不存在的方法 默认调用memcahce去执行.
     *
     * @param string $method
     * @param array $params
     *
     * @return string
     */
    public function __call($method, $params)
    {
        try {
            $ret = call_user_func_array(array($this->memc_obj, $method),$params);
            return $ret;
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }
}

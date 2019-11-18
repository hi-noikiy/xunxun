<?php
namespace Common\Util;
/**
 * Redis操作类
 * @author dufeng
 *
 */

class RedisCache {
    private static $redis = null;
    private $cache;
    /**
     * 获取单例对象
     */
    public static function getInstance() {
        if (self::$redis == null) {
            self::$redis = new RedisCache ();
        }
        return self::$redis;
    }
    /**
     * 构造函数，仅供单例使用
     */
    public function __construct() {
        $this->cache = new \Redis();

//        $this->cache->connect ( C ( 'REDIS.REDISIP' ), C ( 'REDIS.REDISPORT' ) );
        $this->cache->connect (C('REDIS_HOST'), C('REDIS_PORT'));
        $this->cache->auth (C('REDIS_PWD') );
    }
    /**
     * 判断当前Redis服务是否存活;
     *
     * @param ParentRedis $redis
     * @return boolean
     */
    public function isAlive($redis) {
        return true;
    }

    public function getRedis()
    {
        return $this->cache;
    }
    /**
     * 设置值
     *
     * @param string $key
     *        	键
     * @param string $value
     *        	值
     * @param string $expireTime
     */
    public function set($key, $val) {
        if (! $this->isAlive ( $this->cache )) {
            return false;
        }
        return $this->cache->set ( $key,$val );
    }

    /**
     * 设置值
     *
     * @param string $key
     *        	键
     * @param string $value
     *        	值
     * @param string $expireTime
     */
    public function lrem($key, $val) {
        if (! $this->isAlive ( $this->cache )) {
            return false;
        }
        return $this->cache->lrem ( $key,$val );
    }
    /**
     * 设置过多少秒失效
     * @param unknown $key
     * @param unknown $time 单位秒
     * @return boolean
     */
    public function expire($key, $time) {
        if (! $this->isAlive ( $this->cache )) {
            return false;
        }
        return  $this->cache->expire ( $key, $time );
    }

    /**
     * 通过KEY获取数据
     *
     * @param string $key
     *        	KEY名称
     */
    public function get($key) {
        if (! $this->isAlive ( $this->cache )) {
            return false;
        }
        return  ($this->cache->get ( $key ));
    }

    /**
     * 删除一条数据
     *
     * @param string $key
     *        	KEY名称
     */
    public function delete($key) {
        return $this->cache->del ( $key );
    }

    /**
     * 清空数据
     */
    public function flushAll() {
        return $this->cache->flushAll ();
    }
    /**
     * 查询key是否存在
     *
     * @param string $key
     *        	键名称
     */
    public function exists($key) {
        return $this->cache->exists ( $key );
    }
    /**
     * 获取所有键值
     */
    public function keys() {
        return $this->cache->keys ( '*' );
    }

    //判断key是否存在
    public function existskey($key){
        return $this->cache->exists($key);
    }

    /**
     * 保存一个list
     */
    public function setList($key,$data){
        return  $this->cache->rpush($key,$data);
        //return $result;
    }

    /**
     * 获取一个list
     */
    public function getList($key,$offset,$pos){
        return $this->cache->lrange($key, $offset ,$pos);
    }

    /**
     * 删除一个list
     */
    public function rmList($key){
        static $result = true;
        $size = $this->cache->lSize($key);
        if($size != 0) {
            for ($i = 0; $i < $size; $i++) {
                $result = $result && $this->cache->lPop($key);
            }
        }
        return $result;
    }

    /**
     * 为hash表设定一个字段的值
     * @param string $key 缓存key
     * @param string  $field 字段
     * @param string $value 值。
     * @return bool
     */
    public function hSet($key,$field,$value)
    {

        return $this->cache->hSet($key,$field,$value);

    }


    /**
     * 为hash表多个字段设定值。
     * @param string $key
     * @param array $value
     * @return array|bool
     */
    public function hMset($key,$value)
    {

        if (!is_array($value))
            return false;
        return $this->cache->hMset($key, $value);

    }

    /**
     * 返回hash表的一个字段值
     * @param string $key
     * @return $field的值
     */
    public function hGet($key,$field)
    {

        return $this->cache->hGet($key,$field);

    }

    /**
     * 判断hash表中，指定field是不是存在
     * @param string $key 缓存key
     * @param string  $field 字段
     * @return bool
     */
    public function hExists($key,$field)
    {
        return $this->cache->hExists($key,$field);
    }



    /**
     * 删除hash表中指定字段 ,支持批量删除
     * @param string $key 缓存key
     * @param string  $field 字段
     * @return int
     */
    public function hdel($key,$field)
    {


        $delNum = $this->cache->hDel($key,$field);


        return $delNum;
    }

    /**
     * 返回hash表元素个数
     * @param string $key 缓存key
     * @return int|bool
     */
    public function hLen($key)
    {
        return $this->cache->hLen($key);
    }


    /**
     * 返回所有hash表的字段值，为一个索引数组
     * @param string $key
     * @return array|bool
     */
    public function hVals($key)
    {
        return $this->cache->hVals($key);
    }

    public function hSetNx($key)
    {
        return $this->cache->hSetNx($key);
    }


    /**
     * 返回所有hash表的字段值，为一个关联数组
     * @param string $key
     * @return array|bool
     */
    public function hGetAll($key)
    {

        return $this->cache->hGetAll($key);

    }

    //缓存时间
    public function expireAt($key,$time)
    {
        return $this->cache->expireAt($key, time() + $time);
    }

    /**给哈希的某个值加值
     * @param $key  缓存键
     * @param $field    缓存字段值
     * @param $number   数量值
     * @return int
     */
    public function hincrby($key,$field,$number){
        return $this->cache->hincrby($key,$field,$number);
    }



}
?>
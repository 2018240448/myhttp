<?php
namespace Zishu\Myextend\main\reids;
/**
 * 基于原生PHP项目
 * PHP Redis类封装
**/

class PhpRedis{

    protected $redis; //定义实例

    /**
     * 构造函数
    */
    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        $this->redis = new \Redis();  //实例化
        $this->redis->connect($host, $port);//连接redis
    }

    /**
     * 写入缓存
     * @param string $key 存储标识key
     * @param string|mixed $value 存储值
    */
    public function set($key, $value)
    {
        return $this->redis->set($key, $value);
    }

    /**
     * 获取存储数据
     * @param string $key 存储标识
    */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * 删除存储数据
     * @param string|string[]  $key 存储标识
     */
    public function delete($key)
    {
        return $this->redis->delete($key);
    }

    /**
     * 清除缓存
     * @access public
     * @param string $key 存储标识
     *
     * @return boolean
     */
    public function clear($key = null)
    {
        return $this->redis->flushDB($key);
    }

    /**
     * 存储数据并设置时效
     * 将值 value 关联到 key ，并将 key 的过期时间设为 seconds (以秒为单位)。
     * @param string $key 存储标识
     * @param $expire  失效时间 过期时间设为 seconds (以秒为单位)。
     * @param string|mixed $value
     */
    public function setex($key,$value,$expire)
    {
        return $this->redis->setex($key,$expire,$value);
    }

    /**
     * 增量存储 可用于自增数列
     * @param string $key   存储标识
     * @param int    $value 增量
     *
     * @return int 返回整数
     */
    public function inc($key,$value=1)
    {
        return $this->redis->incrby($key,$value);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $key 缓存变量名
     * @param int $step 步长
     *
     * @return false|int
     */
    public function dec(string $key, int $value = 1)
    {
        return $this->redis->incrby($key,$value);
    }

    /*********************************List链数据*********************************/
    /**
     *  Lrange 返回列表中指定区间内的元素
     * 查看list = lrange
     * 区间以偏移量 START 和 END 指定   -1 表示列表的最后一个元素， -2 表示列表的倒数第二个元素
     * @param int $start 开始的元素
     * @param int $end 结束的元素
     * @access public
     */
    public function lview($key, $start = 0, $end = -1)
    {
        return $this->redis->lrange($key, $start = 0, $end = -1);
    }

    /**
     * Lpush 命令将一个或多个值插入到列表头部
     * 加入list = lpush
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $value 存储数据
     * @return boolean
     */
    public function ladd($key, $value)
    {
        return $this->redis->lpush($key,$value);
    }

    /**
     * Lpop 命令用于移除并返回列表的第一个元素。
     * 退出list = lpop
     * @access public
     */
    public function ldel($key)
    {
        return $this->redis->lpop($key);
    }

    /**
     *  Llen 命令用于返回列表的长度
     * 获取长度
     * name 键名
     */
    public function llen($key)
    {
        return $this->redis->llen($key);

    }

    /*********************************Set无序集合*********************************/
    /**
     * Redis Smembers 命令返回集合中的所有的成员
     * 查看Set = smembers
     * @access public
     */
    public function sview($key)
    {
        return $this->redis->smembers($key);
    }

    /**
     * Redis Scard 命令返回集合中元素的数量。
     * 查看Set存在个数 = scard
     * @access public
     */
    public function scount($key)
    {
        return $this->redis->scard($key);
    }

    /**
     * Sscan 命令用于迭代集合中键的元素
     * HSCAN 命令用于迭代哈希表中的键值对。
     * 查看Set存在个数 = sscan
     * 检索 匹配
     * @access public
     */
    public function sscan($key, $pattern='*', $cursor=null, $count = 3000,$type=1)
    {
        if($type == 1){
            $result = $this->redis->sscan($key,$cursor, $pattern, $count);
        }else{
            $result = $this->redis->hscan($key,$cursor, $pattern, $count);
        }
        if (is_null($result)) {
            return false;
        }
        return $result;
    }

    /**
     *  Sadd 命令将一个或多个成员元素加入到集合中，已经存在于集合的成员元素将被忽略。即存在相同的值则不加入（不充分）
     * 添加Set元素 = sadd
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $value 存储数据
     * @return boolean
     * 列表 不重复添加
     */
    public function sadd($key, $value)
    {
        $result = $this->redis->sadd($key, $value);
        return $result;
    }

    /**
     * Srem 命令用于移除集合中的一个或多个成员元素，不存在的成员元素会被忽略。
     * 删除Set = srem
     * @access public
     */
    public function sdel($key, $value)
    {
        $result = $this->redis->srem($key, $value);
        return $result;
    }

    /**
     *  Srandmember 命令用于返回集合中的一个随机元素。
     * 随机取Set元素 = srandmember
     * @access public
     * @param string $key 缓存变量名
     * @param integer $num 数量
     * @return array
     */
    public function srand($key, $num)
    {
        $result = $this->redis->srandmember($key, $num);
        return $result;
    }

    /**
     * 移除并返回Set集合中的一个随机元素 = spop
     * @access public
     * @param string $key 缓存变量名
     * @return array
     */
    public function spop($key)
    {
        $redis = $this->is_master();
        $result = $redis->spop($key);
        return $result;
    }

    /*********************************Hase*********************************/
    /**
     *  Hset 命令用于为哈希表中的字段赋值 。
     * 添加Hase值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:新建值 0：覆盖值
     * 覆盖掉原来的值
     */
    public function hadd($key, $field, $value)
    {
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $result = $this->redis->hset($key, (string)$field, $value);
        return $result;
    }

    /**
     * Hsetnx 命令用于为哈希表中不存在的的字段赋值
     * 只有在字段 field 不存在时，设置哈希表字段的值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:新建值, 0：覆盖值
     */
    public function haddnx($key, $field, $value)
    {
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $result = $this->redis->hsetnx($key, $field, $value);
        return $result;
    }

    /**
     * Hdel 命令用于删除哈希表 key 中的一个或多个指定字段，不存在的字段将被忽略。
     * 删除Hase值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hdel($key, $field)
    {
        $result = $this->redis->hdel($key, $field);
        return $result;
    }

    /**
     * Hget 命令用于返回哈希表中指定字段的值。
     * 查Hase值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hget($key, $field)
    {
        $result = $this->redis->hget($key, (string)$field);
        return $result;
    }

    /**
     * 获取在哈希表中指定 key 的所有字段和值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hgetAll($key)
    {
        $result = $this->redis->hgetall($key);
        return $result;
    }

    /**
     * 获取哈希表中的所有字段
     * 查Hase指定键所有值
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hkeys($key)
    {
        $result = $this->redis->hkeys($key);
        return $result;
    }

    /**
     * 获取哈希表中字段的数量
     * 查Hase长度
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hlen($key)
    {
        $result = $this->redis->hlen($key);
        return $result;
    }

    /**
     * Hase自增
     * 为哈希表 key 中的指定字段的整数值加上增量 increment 。
     * @access public
     * @param string $key 缓存变量名
     * @return integer 1:成功
     */
    public function hinc($key, $field, $num = 1)
    {
        $result = $this->redis->hincrby($key,(string)$field, $num);
        return $result;
    }
    /*********************************坐标相关*********************************/
    /**
     *  用于存储指定的地理空间位置，可以将一个或多个经度(longitude)、纬度(latitude)、位置名称(member)添加到指定的 key 中。
     * 坐标添加
     * @param $key
     * @param $lng
     * @param $lat
     * @param $member 数量
     * @return mixed
     */
    public function gadd($key, $lng, $lat,$member)
    {
        $result = $this->redis->geoadd($key,$lng, $lat, $member);
        return $result;
    }

    /**
     * 以给定的经纬度为中心， 返回键包含的位置元素当中， 与中心的距离不超过给定最大距离的所有位置元素。
     * 坐标范围检索
     * @param $key
     * @param $lng
     * @param $lat
     * @param $radius 半径或周围
     * @param string $unit 单位
     *  WITHDIST: 在返回位置元素的同时， 将位置元素与中心之间的距离也一并返回。
     *  WITHCOORD: 将位置元素的经度和纬度也一并返回。
     * ASC: 查找结果根据距离从近到远排序。
     * DESC: 查找结果根据从远到近排序。
     * @return mixed
     */
    public function gradius($key,$lng,$lat,$radius,$unit='km')
    {
        $result = $this->redis->georadius($key,$lng,$lat,$radius,$unit,['WITHDIST','ASC']);
        return $result;
    }

    // 可以继续封装其他常用的Redis命令
}
<?php
namespace Zishu\Myextend\main;
/**
 * PHP Redis类封装
**/

class MyRedis{

    /**
     *类对象实例数组
     *共有静态变量
     * @param mixed $_instance存放实例
     */
    private static $_instance = array();

    /**
     * 配置参数
     * @var array
     */
    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
    ];

    /**
     * 构造函数
     * @access public
     * @param array $options 缓存参数
     */
    public function __construct(array $options = [])
    {
        $options=[];
        //配置redis参数
//        $options = [
//            // 驱动方式
//            'type' => 'Redis',
//            'host' => '127.0.0.1,127.0.0.1,127.0.0.1', // 服务器地址
//            'port' => '6379,6380,6381',
//            'password' => '',
//            'expire' => 0,    // 全局缓存有效期（0为永久有效）
////            'prefix'=>  'redis_',   // 缓存前缀
//        ];
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
            //此处进行分布式配置
            $params = array(
                'hosts' => explode(',', $this->options['host']),
                'ports' => explode(',', $this->options['port']),
                'password' => explode(',', $this->options['password']),
                'select' => [0],
            );
            //拼接参数
            $hostsNum = count($params['hosts']);
            for ($i = 0; $i < $hostsNum; $i++) {
                $host = $params['hosts'][$i];
                $port = $params['ports'][$i] ? $params['ports'][$i] : $params['ports'][0];
                $password = isset($params['password'][$i]) ? $params['password'][$i] : $params['password'][0];
                $select = isset($params['select'][$i]) ? $params['select'][$i] : $params['select'][0];
                $redisParams = array('host' => $host, 'port' => $port, 'password' => $password, 'select' => $select);
                if (!isset(self::$_instance[$i]) || !(self::$_instance[$i] instanceof \think\cache\driver\Redis)) {
                    try {
                        self::$_instance[$i] = new \think\cache\driver\Redis($redisParams);
                    } catch (\Exception $e) {

                    }
                }
            }
            self::$_instance = array_merge(self::$_instance);//防止中间端口的redis 没有启用

            return self::$_instance[0]->handler;

        } else {
            $params = array(
                'hosts' => $this->options['host'],
                'ports' => $this->options['port'],
                'password' => $this->options['password'],
                'select' => $this->options['select'],
            );
            $redisParams = array('host' => $params['hosts'], 'port' => $params['ports'], 'password' => $params['password'], 'select' => $params['select']);

            return self::$_instance = new \think\cache\driver\Redis($redisParams);
        }

    }

    /**
     * 判断是否master/slave,调用不同的master或者slave实例
     * @access public
     * @param boolen $master
     * @return type
     */
    public function is_master($master = true)
    {
        $dd = self::$_instance;
        if(is_array($dd)){
            $count = count($dd);
            $i = $master || 1 == $count ? 0 : rand(1, $count - 1);
            if ($master) {//找主服务器
                for ($j = 0; $j < $count; $j++) {
                    $info = self::$_instance[$j]->handler->info();
                    if ($info['role'] == 'master') {
                        //返回每一个实例的句柄
                        return self::$_instance[$j]->handler;
                    }
                }
            }
            //返回每一个实例的句柄
            return self::$_instance[$i]->handler;
        }else{
            return self::$_instance->handler;
        }


    }

    /**
     * 管道
     * 开通
     */
    public function pipeline()
    {
        $redis = $this->is_master(false);
        return $redis->PIPELINE();
    }

    /**
     * 事务
     * 开始
     */
    public function multi()
    {
        $redis = $this->is_master(false);
        return $redis->multi();
    }

    /**
     * 事务
     * 运行
     */
    public function exec()
    {
        $redis = $this->is_master(false);
        return $redis->exec();
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name): bool
    {
        $redis = $this->is_master(false);
        return $redis->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $redis = $this->is_master(false);
        $value = $redis->get($this->getCacheKey($name));

        if (!$value) {
            return $default;
        }
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
        return (null === $jsonData) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param integer $expire 有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }

        $key = $this->getCacheKey($name);
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $redis = $this->is_master();
        if (is_int($expire) && $expire) {
            $result = $redis->setex($key, $expire, $value);
        } else {
            $result = $redis->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        $redis = $this->is_master();
        $key = $this->getCacheKey($name);
        return $redis->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        $redis = $this->is_master();
        $key = $this->getCacheKey($name);
        return $redis->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function delete($name): bool
    {
        $redis = $this->is_master();
        return $redis->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null): bool
    {
        $redis = $this->is_master();
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $redis->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $redis->flushDB();
    }

    /**
     * 删除缓存标签
     * @access public
     * @param array $keys 缓存标识列表
     * @return void
     */
    public function clearTag(array $keys): void
    {
        // 指定标签清除
        $this->handler->del($keys);
    }

    /*********************************List链数据*********************************/
    /**
     * 查看list = lrange
     * @access public
     */
    public function lview($name, $start = 0, $end = -1)
    {
        $redis = $this->is_master(false);
        $result = $redis->lrange($name, $start, $end);
        if (is_null($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 加入list = lpush
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @return boolean
     */
    public function ladd($name, $value)
    {
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $redis = $this->is_master();
        $result = $redis->lpush($name, $value);
        return $result;
    }

    /**
     * 退出list = lpop
     * @access public
     */
    public function ldel($name, $value)
    {
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $redis = $this->is_master();
        $result = $redis->lpop($name, $value);
        return $result;
    }

    /**
     * 获取长度
     * name 键名
     */
    public function llen($name)
    {
        $redis = $this->is_master();
        return $redis->llen($name);

    }
    /*********************************Set无序集合*********************************/
    /**
     * 查看Set = smembers
     * @access public
     */
    public function sview($name, $value = null)
    {
        $redis = $this->is_master(false);
        if (is_null($value)) {
            $result = $redis->smembers($name);
            if (is_null($result)) {
                return false;
            }
            return $result;
        } else {
            $result = $redis->smembers($name, $value);
            if (is_null($result)) {
                return false;
            }
            return $result;
        }

    }

    /**
     * 查看Set存在个数 = scard
     * @access public
     */
    public function scount($name)
    {
        $redis = $this->is_master(false);
        $result = $redis->scard($name);
        if (is_null($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 查看Set存在个数 = sscan
     * 检索 匹配
     * @access public
     */
    public function sscan($key, $pattern='*', $cursor=null, $count = 3000,$type=1)
    {
        $redis = $this->is_master(false);
        if($type == 1){
            $result = $redis->sscan($key,$cursor, $pattern, $count);
        }else{
            $result = $redis->hscan($key,$cursor, $pattern, $count);
        }
        if (is_null($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 添加Set元素 = sadd
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @return boolean
     * 列表 不重复添加
     */
    public function sadd($name, $value)
    {
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $redis = $this->is_master();
        $result = $redis->sadd($name, $value);
        return $result;
    }

    /**
     * 删除Set = srem
     * @access public
     */
    public function sdel($name, $value)
    {
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $redis = $this->is_master();
        $result = $redis->srem($name, $value);
        return $result;
    }

    /**
     * 随机取Set元素 = srandmember
     * @access public
     * @param string $name 缓存变量名
     * @param integer $num 数量
     * @return array
     */
    public function srand($name, $num)
    {
        $redis = $this->is_master(false);
        $result = $redis->srandmember($name, $num);
        return $result;
    }

    /**
     * 移除并返回Set集合中的一个随机元素 = spop
     * @access public
     * @param string $name 缓存变量名
     * @return array
     */
    public function spop($name)
    {
        $redis = $this->is_master();
        $result = $redis->spop($name);
        return $result;
    }

    /*********************************Hase*********************************/
    /**
     * 添加Hase值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:新建值 0：覆盖值
     * 覆盖掉原来的值
     */
    public function hadd($name, $field, $value)
    {
        $redis = $this->is_master();
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $result = $redis->hset($name, (string)$field, $value);
        return $result;
    }

    /**
     * 只有在字段 field 不存在时，设置哈希表字段的值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:新建值, 0：覆盖值
     */
    public function haddnx($name, $field, $value)
    {
        $redis = $this->is_master();
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        $result = $redis->hsetnx($name, $field, $value);
        return $result;
    }

    /**
     * 删除Hase值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hdel($name, $field)
    {
        $redis = $this->is_master();
        $result = $redis->hdel($name, $field);
        return $result;
    }

    /**
     * 查Hase值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hget($name, $field)
    {
        $redis = $this->is_master(false);
        $result = $redis->hget($name, (string)$field);
        return $result;
    }

    /**
     * 获取在哈希表中指定 key 的所有字段和值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hgetAll($name)
    {
        $redis = $this->is_master(false);
        $result = $redis->hgetall($name);
        return $result;
    }

    /**
     * 查Hase指定键所有值
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hkeys($name)
    {
        $redis = $this->is_master(false);
        $result = $redis->hkeys($name);
        return $result;
    }

    /**
     * 查Hase长度
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hlen($name)
    {
        $redis = $this->is_master(false);
        $result = $redis->hlen($name);
        return $result;
    }

    /**
     * Hase自增
     * @access public
     * @param string $name 缓存变量名
     * @return integer 1:成功
     */
    public function hinc($name, $field, $num = 1)
    {
        $redis = $this->is_master();
        $result = $redis->hincrby($name,(string)$field, $num);
        return $result;
    }
    /*********************************坐标相关*********************************/
    /**
     * 坐标添加
     * @param $name
     * @param $lng
     * @param $lat
     * @return mixed
     */
    public function gadd($name, $lng, $lat)
    {
        $redis = $this->is_master();
        $result = $redis->geoadd('map',$lng, $lat, $name);
        return $result;
    }

    /**
     * 坐标范围检索
     * @param $name
     * @param $lng
     * @param $lat
     * @return mixed
     */
    public function gradius($lng,$lat,$radius,$unit='km')
    {
        $redis = $this->is_master();
        $result = $redis->georadius('map',$lng,$lat,$radius,$unit,['WITHDIST','ASC']);
        return $result;
    }
    /*********************************特殊命令*********************************/
    /**
     * 获取特定的key值
     * @access public
     * @param string $name 缓存变量名
     * @return array
     */
    public function keys($name)
    {
        $redis = $this->is_master(false);
        $result = $redis->keys($name);
        return $result;
    }

    /**
     * 清除key值
     * @access public
     * @param string $name 缓存变量名
     * @return array
     */
    public function del($name)
    {
        $redis = $this->is_master();
        $result = $redis->unlink($name);
        return $result;
    }

    /**
     * 清除所有缓存
     * @access public
     * @param string $name 缓存变量名
     * @return array
     */
    public function alldel()
    {
        $redis = $this->is_master();
        $result = $redis->flushall();
        return $result;
    }
    /**
     * 查看所有Key存在个数 = scan
     * 检索 匹配
     * @access public
     */
    public function scan($pattern='*', $iterator=null)
    {

        $redis = $this->is_master(false);

        $result = $redis->scan($iterator, $pattern,3000);

        if (is_null($result)) {
            return false;
        }
        return $result;

    }

    /**
     * 发布订阅消息
     * @access public
     * @return  string $channel 通道名称
     */
    public function publish(string $channel,$value): bool
    {
        $redis = $this->is_master(false);
        $dd = $redis->publish($channel,$value);
        if($dd>0){
            return true;
        }
        return false;
    }

    /**
     * 接收订阅消息
     * @access public
     * @return  string $channel 通道名称
     */
    public function subscribe(array $channel,$value): string
    {
        $redis = $this->is_master(false);
        return  $redis->subscribe($channel,$value);
    }

}
## MyPHPExtend （个人PHP扩展）
####个人PHP扩展  
#####① http curl请求封装,类名称 MyHttp;  
#####② 请求参数加密解密封装,类名称 MyParams;  
#####③ redis常用函数封装,redis;  
#####④ 日志记录类;

` php:>=8.0 `  

 ` composer require zishu/myextend `  


## 使用  
###1. MyHttp类使用(http请求类)

```
//get实例引用
\Zishu\Myextend\main\MyHttp::get('url',[]);

//post实例引用
\Zishu\Myextend\main\MyHttp::post('url',[]);
```

###2. MyLog类使用(日志记录类)

```
//PHP 项目 操作日志记录
$log = new \Zishu\Myextend\main\MyLog('admin'); //实例化 , 参数为类型.admin即项目应用模块的类型

//日志记录
$userId = 1;
$log->log($userId);

//获取日志
$time = time(); //可以选中的时间
$log->get_log($time);

//删除日志
$time = time(); //可以选中的时间
$log->del_log($time);
```

###3. MyParams类使用(参数加密解密类)

```
//加密
$params=[];//需要加密的参数数组
$key = '';//动态密钥 或者 固定配置的常量
$params = new \Zishu\Myextend\main\MyParams();
$params->reqEncode($params,$key); //解密
```


```   
//解密
$params=[
  'key'=>'',//客户端请求返回，或者 固定配置的常量
  'data'=>'',//客户端请求的加密参数
];//参数
$params = new \Zishu\Myextend\main\MyParams();
$params->reqDecode($params); //解密
```

###4. redis类（分为PHP原生项目PhpRedis类和 ThinkPHP项目的TpRedis类）

```
//ThinkPHP 项目
$redis = new \Zishu\Myextend\main\redis\TpRedis(); //实例化

//加入缓存
$data = 'hello redis';
$redis->set('redis_name',$data);
        
// 获取缓存
$data = $redis->get('redis_name');
```

```
//ThinkPHP 项目
$redis = new \Zishu\Myextend\main\redis\TpRedis(); //实例化

//加入缓存
$data = 'hello redis';
$redis->set('redis_name',$data);
        
// 获取缓存
$data = $redis->get('redis_name');
```

###5. 继续增加中...

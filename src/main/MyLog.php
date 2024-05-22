<?php
namespace Zishu\Myextend\main;

/***
 * 日志类 存储在本地工作目录下路径下
 * 操作(使用)日志 写入文件 读取 删除
 * @param type 可定义为不同的日志路径 如 admin
 */

class MyLog{

    private $baseDir; //基本路径
    private $subDir; //子目录
    private $fileName; //文件名称及后缀

    /**
     * 构造
     * $type  admin  index  api 等
     */
    public function __construct($type='admin')
    {
        define("DS",DIRECTORY_SEPARATOR); //定义常量路径
//        define("ROOT",getcwd().DS); //当前工作目录
//        define("ROOT", app()->getRootPath()); //ThinkPHP 获取当前运行脚本所在的文档根目录
        define("ROOT",$_SERVER['DOCUMENT_ROOT'].DS); //获取当前运行脚本所在的文档根目录 (入口文件下的目录)
        $this->baseDir = ROOT."use_log/".$type; //基本路径
        $this->subDir = date("Ym");  //子目录 年月份为名
        $this->fileName = date("Ymd") . ".txt"; //文件名称及后缀 年月份天为名
    }

    /**
     * 记录日志 日志内容
     * @param $user_id 用户id
     * 可记录
     */
public function log($user_id)
    {
        //记录数据组成
        $content = [
            "userId"=>$user_id,//请求用户id
            "ip"=> $_SERVER['REMOTE_ADDR'],//访问ip
            "time"=>date('Y-m-d H:i:s',time()),//请求时间
            "uri"=>$_SERVER['REQUEST_URI'],//请求的接口
            "ua"=>$_SERVER['HTTP_USER_AGENT'], //用户代理 浏览器
            "method"=>$_SERVER['REQUEST_METHOD'],//请求方式
        ];

        if($content['method'] == 'GET'){
            $content['param'] = $_GET; //请求参数
        }else{
            $content['param'] = $_POST; //请求参数
        }

        //是否存在文件 不存在则新增
        $fileDir = $this->baseDir . "/" . $this->subDir;
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }

        $filePath = $fileDir . "/" . $this->fileName; //文件的完整路径
        $logContent =json_encode($content)."\r\n"; // 转为json_encode 并 每条记录换行
        error_log($logContent, 3, $filePath); //写入文件中
    }

    /**
     * 获取并解析日志文件
     * @param $time 为时间戳
     */
public function get_log($time){
        $list=[];
        $sub = date('Ym',$time);
        $name = date('Ymd',$time).".txt";
        $file= $this->baseDir . "/" . $sub. "/" .$name;

        //是否存在
        if(file_exists($file)){
            $data = file_get_contents($file);
            //内容以换行符分割为数组
            $data=explode("\r\n",$data);
            if(is_array($data)){
                foreach ($data as $key=>$vs){
                    if(!empty($vs)) {
                        $list[] = json_decode($vs, true);
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 删除日志
     * @param $time 为文件名
     */
   public function del_log($time){

        $sub = date('Ym',$time);
        $name = date('Ymd',$time).".txt";
        $file= $this->baseDir . "/" . $sub. "/" .$name;

        //是否存在
        if(file_exists($file)){
            unlink($file); //删除文件
        }
        return true;
    }
}
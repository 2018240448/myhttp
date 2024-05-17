<?php
namespace Zishu\Myextend\main;
/**
 * PHP 请求参数加密解密类
 * AES对称加密算法
 * ECB：将明文分成若干段相同的小段，然后对每一小段进行加密
 * 优点:1.简单；2.有利于并行计算；3.误差不会被传送；
 * 缺点:1.不能隐藏明文的模式；2.可能对明文进行主动攻击；
 *
 * CBC：这种模式是先将明文切分成若干小段，然后每一小段与初始块或者上一段的密文段进行异或运算后，再与密钥进行加密
 * 优点：1.不容易主动攻击,安全性好于ECB,适合传输长度长的报文,是SSL、IPSec的标准。
 * 缺点：1.不利于并行计算；2.误差传递；3.需要初始化向量IV
**/

class MyParams{

    private $iv; //初始化向量 长度 16字节  如：GS56SGSsJ_myblog
    private $key; //$encrypt_key  密钥长度 16字节 如：固定字符+time()  badmin1715913436

    /**
     * 构建
    */
    public function __construct(array $arr){
        $this->iv = $arr['iv'];
        $this->key = $arr['key'];
    }
    /**
     * 请求参数 - 加密
     * AES-128-CBC 使用128位密钥进行加密
     * openssl_decrypt
     * @param $data 加密的数据
     * @param $ekey 动态密钥， 拼接后总共16字节
     */
    public function reqEncode($data,$ekey)
    {
        $ekey = $ekey.$this->key;
        return openssl_encrypt(json_encode($data), "AES-128-CBC", $ekey, 0, $this->iv);
    }

    /**
     *  请求参数 - 解密
     * AES-128-CBC 使用128位密钥进行加密
     * openssl_decrypt
     * @param $data 加密的数据
     * @param $ekey 动态密钥， 拼接后总共16字节
     */
   public function reqDecode($params)
    {
        if (!empty($params)) {
            $ekey = $params['key'] . $this->key;
            $data = openssl_decrypt($params['data'], "AES-128-CBC", $ekey, 0, $this->iv);
            return json_decode($data, true);
        }
        return false;
    }

}
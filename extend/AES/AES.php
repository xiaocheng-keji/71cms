<?php
namespace AES;

class AES
{
    public $privateKey = "abchdkeidngidosi";
    public $iv = "idjdhaigndiwqkdh";
    public $method = 'AES-128-CBC';


        //加密
    public function encrypt($data)
    {
        return bin2hex(openssl_encrypt($data, $this->method, $this->privateKey, 1, $this->iv));//加密
    }

    //解密
    public function decrypt($data)
    {
        return openssl_decrypt(hex2bin($data), 'AES-128-CBC', $this->privateKey, OPENSSL_RAW_DATA,$this->iv);
    }

}
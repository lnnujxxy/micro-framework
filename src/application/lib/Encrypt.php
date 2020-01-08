<?php
namespace Pepper\Framework\Lib;

class Encrypt
{
    /**
     * decrypt_aes()
     * 用加密key将数据解密
     *
     * @param $decrypt_key
     * @param $input
     *
     * @return bool|string
     */
    static public function decrypt_aes($decrypt_key, $input)
    {
        try {
            list($encrypted_data, $iv) = explode('::', $input, 2);
            return openssl_decrypt($encrypted_data, 'aes-256-cbc', $decrypt_key, 0, $iv);
        } catch (\Exception $ex) {
            Logger::log('encrypt_wf', 'decrypt error', array('key' => $decrypt_key, 'errcode' => $ex->getCode(),
                'errmsg' => $ex->getMessage(), 'line' => __LINE__));
        }

        return false;
    }


    /**
     * encrypt_aes
     * 加密数据
     *
     * @param $encrypt_key
     * @param $input
     *
     * @return bool|string
     */
    static public function encrypt_aes($encrypt_key, $input)
    {
        try {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encrypted = openssl_encrypt($input, 'aes-256-cbc', $encrypt_key, 0, $iv);
            return $encrypted . '::' . $iv;
        } catch (\Exception $ex) {
            Logger::log('encrypt_wf', 'decrypt error', array('key' => $encrypt_key, 'errcode' => $ex->getCode(),
                'errmsg' => $ex->getMessage(), 'line' => __LINE__));
        }
        return false;
    }
}
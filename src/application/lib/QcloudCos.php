<?php
namespace Pepper\Framework\Lib;

use Qcloud\Cos\Client as CosClient;

class QcloudCos
{
    private $config;
    public function __construct ($bucket = 'goods-1257256615', $region = 'ap-beijing') {
        $this->config = array(
            'Url' => 'https://sts.api.qcloud.com/v2/index.php',
            'Domain' => 'sts.api.qcloud.com',
            'Proxy' => '',
            'SecretId' => SECRET_ID, // 固定密钥
            'SecretKey' => SECRET_KEY, // 固定密钥
            'Bucket' => $bucket,
            'Region' => $region,
            'AllowPrefix' => '*', // 必填，这里改成允许的路径前缀，这里可以根据自己网站的用户登录态判断允许上传的目录，例子：* 或者 a/* 或者 a.jpg
        );
    }

    // json 转 query string
    function json2str($obj, $notEncode = false) {
        ksort($obj);
        $arr = array();
        foreach ($obj as $key => $val) {
            !$notEncode && ($val = urlencode($val));
            array_push($arr, $key . '=' . $val);
        }
        return join('&', $arr);
    }

    // 计算临时密钥用的签名
    function getSignature($opt, $key, $method) {
        $config = $this->config;
        $formatString = $method . $config['Domain'] . '/v2/index.php?' . $this->json2str($opt, 1);
        $sign = hash_hmac('sha1', $formatString, $key);
        $sign = base64_encode(hex2bin($sign));
        return $sign;
    }

    // 获取临时密钥
    function getTempKeys() {
        $config = $this->config;
        // 判断是否修改了 AllowPrefix
        if ($config['AllowPrefix'] === '_ALLOW_DIR_/*') {
            return array('error'=> '请修改 AllowPrefix 配置项，指定允许上传的路径前缀');
        }
        $ShortBucketName = substr($config['Bucket'],0, strripos($config['Bucket'], '-'));
        $AppId = substr($config['Bucket'], 1 + strripos($config['Bucket'], '-'));
        $policy = array(
            'version'=> '2.0',
            'statement'=> array(
                array(
                    'action'=> array(
                        // // 这里可以从临时密钥的权限上控制前端允许的操作
                        'name/cos:*', // 这样写可以包含下面所有权限
                        // // 列出所有允许的操作
                        // // ACL 读写
                        // 'name/cos:GetBucketACL',
                        // 'name/cos:PutBucketACL',
                        // 'name/cos:GetObjectACL',
                        // 'name/cos:PutObjectACL',
                        // // 简单 Bucket 操作
                        // 'name/cos:PutBucket',
                        // 'name/cos:HeadBucket',
                        // 'name/cos:GetBucket',
                        // 'name/cos:DeleteBucket',
                        // 'name/cos:GetBucketLocation',
                        // // Versioning
                        // 'name/cos:PutBucketVersioning',
                        // 'name/cos:GetBucketVersioning',
                        // // CORS
                        // 'name/cos:PutBucketCORS',
                        // 'name/cos:GetBucketCORS',
                        // 'name/cos:DeleteBucketCORS',
                        // // Lifecycle
                        // 'name/cos:PutBucketLifecycle',
                        // 'name/cos:GetBucketLifecycle',
                        // 'name/cos:DeleteBucketLifecycle',
                        // // Replication
                        // 'name/cos:PutBucketReplication',
                        // 'name/cos:GetBucketReplication',
                        // 'name/cos:DeleteBucketReplication',
                        // // 删除文件
                        // 'name/cos:DeleteMultipleObject',
                        // 'name/cos:DeleteObject',
                        // 简单文件操作
                        'name/cos:PutObject',
                        'name/cos:PostObject',
                        'name/cos:AppendObject',
                        'name/cos:GetObject',
                        'name/cos:HeadObject',
                        'name/cos:OptionsObject',
                        'name/cos:PutObjectCopy',
                        'name/cos:PostObjectRestore',
                        // 分片上传操作
                        'name/cos:InitiateMultipartUpload',
                        'name/cos:ListMultipartUploads',
                        'name/cos:ListParts',
                        'name/cos:UploadPart',
                        'name/cos:CompleteMultipartUpload',
                        'name/cos:AbortMultipartUpload',
                    ),
                    'effect'=> 'allow',
                    'principal'=> array('qcs'=> array('*')),
                    'resource'=> array(
                        'qcs::cos:' . $config['Region'] . ':uid/' . $AppId . ':prefix//' . $AppId . '/' . $ShortBucketName . '/',
                        'qcs::cos:' . $config['Region'] . ':uid/' . $AppId . ':prefix//' . $AppId . '/' . $ShortBucketName . '/' . $config['AllowPrefix']
                    )
                )
            )
        );
        $policyStr = str_replace('\\/', '/', json_encode($policy));
        $Action = 'GetFederationToken';
        $Nonce = rand(10000, 20000);
        $Timestamp = time();
        $Method = 'GET';
        $params = array(
            'Action'=> $Action,
            'Nonce'=> $Nonce,
            'Region'=> '',
            'SecretId'=> $config['SecretId'],
            'Timestamp'=> $Timestamp,
            'durationSeconds'=> 7200,
            'name'=> '',
            'policy'=> $policyStr
        );
        $params['Signature'] = urlencode($this->getSignature($params, $config['SecretKey'], $Method));
        $url = $config['Url'] . '?' . $this->json2str($params, 1);
        $ch = curl_init($url);
        $config['Proxy'] && curl_setopt($ch, CURLOPT_PROXY, $config['Proxy']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if(curl_errno($ch)) $result = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, 1);
        if ($result['code'] != 0) {
            Logger::warning("qcloud cos warning: " . json_encode($result));
        }
        return $result['data'];
    }

    // 上传文件
    public function putObject($key, $body) {
        $cosClient = new CosClient(
            array(
                'region' => $this->config['Region'],
                'credentials'=> array(
                    'secretId'   => $this->config['SecretId'],
                    'secretKey' => $this->config['SecretKey']
                )
            )
        );
        try {
            $cosClient->putObject(array(
                'Bucket' => $this->config['Bucket'],
                'Key' => $key,
                'Body' => $body));
        } catch (\Exception $e) {
            Logger::fatal('Qcloud COS putObject ' . $e->getMessage(), array_merge($this->config, ['key' => $key]), $e->getCode());
            try {
                $cosClient->putObject(array(
                    'Bucket' => $this->config['Bucket'],
                    'Key' => $key,
                    'Body' => $body));
            } catch (\Exception $e) {
                Logger::fatal('Qcloud COS putObject ' . $e->getMessage(), array_merge($this->config, ['key' => $key]), $e->getCode());
                return '';
            }
        }

        try {
            $signedUrl = $cosClient->getObjectUrl($this->config['Bucket'], $key);
            return $signedUrl;
        } catch (\Exception $e) {
            return '';
        }
    }
}

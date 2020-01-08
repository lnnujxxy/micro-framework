<?php

namespace Pepper\Lib;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\ConnectionPool\Selectors\RandomSelector;
use Elasticsearch\ConnectionPool\SimpleConnectionPool;

class ES
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * 操作超时（如果es服务不正常，一般是通过此值控制的超时）
     * @var int
     */
    protected $timeout = 3;

    /**
     * 建立网络连接超时
     * @var int
     */
    protected $connectTimeout = 3;

    /**
     * 最大搜索起始
     * @var int
     */
    protected $maxFrom = 1000;
    /**
     * 最大单次查询条数
     * @var int
     */
    protected $maxSize = 100;

    private function __construct($hosts)
    {
        $params = [
            'client' => [
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->timeout
            ]
        ];

        $builder = ClientBuilder::create()->setHosts($hosts)->setConnectionParams($params);
        // 默认连接池方案在一次请求失败后，会标记此服务异常60s。导致期间所有后续请求均无链接可用，抛出No alive nodes的异常
        $builder->setConnectionPool(SimpleConnectionPool::class);
        $builder->setSelector(RandomSelector::class);
        $this->client = $builder->build();
    }

    /**
     * 获取es实例
     * @param array $hosts 格式：["user:pass@ip:port"]
     * @return self|Client
     */
    public static function getInstance($hosts)
    {
        static $clients = [];
        $cacheKey = md5(json_encode($hosts));
        if (!isset($clients[$cacheKey])) {
            $clients[$cacheKey] = new self($hosts);
        }
        return $clients[$cacheKey];
    }

    /**
     * 搜索
     * @param string $index
     * @param string $type
     * @param array $params
     * @param int $from 最大1000
     * @param int $size 最大100
     * @return array|bool
     */
    public function search($index, $type, array $params, $from = 0, $size = 10)
    {
        if ($from > $this->maxFrom || $size > $this->maxSize) {
            return false;
        }
        return $this->client->search([
            'index' => $index,
            'type' => $type,
            'body' => $params,
            'from' => (int)$from,
            'size' => (int)$size,
        ]);
    }

    /**
     * 新增文档（index操作）
     * @param string $index
     * @param string $type
     * @param number $id
     * @param array $data
     * @throws \Exception
     * @return array
     */
    public function add($index, $type, $id, array $data)
    {
        return $this->client->index([
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $data,
        ]);
    }

    /**
     * 查询单条记录
     * @param string $index
     * @param string $type
     * @param number $id
     * @throws \Exception
     * @return array
     */
    public function get($index, $type, $id)
    {
        return $this->client->get([
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ]);
    }

    /**
     * 更新文档
     * @param string $index
     * @param string $type
     * @param number $id
     * @param array $data
     * @throws \Exception
     * @return array
     */
    public function update($index, $type, $id, array $data)
    {
        return $this->client->update([
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $data,
        ]);
    }

    /**
     * 删除文档
     * @param string $index
     * @param string $type
     * @param number $id
     * @throws \Exception
     * @return array
     */
    public function delete($index, $type, $id)
    {
        return $this->client->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ]);
    }

    /**
     * 批量操作
     * @doc https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     * @param $index
     * @param $type
     * @param array $body
     * @return array
     */
    public function bulk($index, $type, array $body)
    {
        return $this->client->bulk([
            'index' => $index,
            'type' => $type,
            'body' => $body,
        ]);
    }

    /**
     * 透传未封装方法
     * @param $name
     * @param $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->client, $name)) {
            return call_user_func_array([$this->client, $name], $arguments);
        }
        return null;
    }
}

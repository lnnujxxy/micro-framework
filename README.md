# framework
### 代码结构
```
├── .is_test_env // 配置指定环境
├── README.md
├── composer.json // composer 配置
├── composer.lock // composer lock
├── config // 配置文件
│   └── server 
│       ├── server_conf.common.php // 通用配置文件
│       ├── server_conf.release.php // 线上服务配置
│       └── server_conf.test.php // 测试服务配置
├── phpunit.xml // 测试phpunit配置
├── src
│   ├── application
│   │   ├── controller // controller层
│   │   │   ├── BaseController.php
│   │   │   ├── FollowController.php
│   │   │   ├── TestController.php
│   │   │   └── UserController.php
│   │   ├── dao // dao 数据库操作层
│   │   │   ├── DAOFollowLog.php
│   │   │   ├── DAOFollower.php
│   │   │   ├── DAOFollowing.php
│   │   │   ├── DAOProxy.php
│   │   │   └── DAOUser.php
│   │   ├── lib // 通用类库
│   │   │   ├── BizException.php
│   │   │   ├── Consume.php
│   │   │   ├── Context.php
│   │   │   ├── Curl.php
│   │   │   ├── Degraded.php
│   │   │   ├── Encrypt.php
│   │   │   ├── InputHelper.php
│   │   │   ├── Interceptor.php
│   │   │   ├── LocalCache.php
│   │   │   ├── Lock.php
│   │   │   ├── Logger.php
│   │   │   ├── RedisProxy.php
│   │   │   ├── Restrict.php
│   │   │   ├── Token.php
│   │   │   ├── Util.php
│   │   │   └── Validate.php
│   │   └── model // model层代码
│   │       ├── Follow.php
│   │       ├── User.php
│   │       └── WxAuth.php
│   ├── process 
│   │   ├── cron // cron 定时任务
│   │   ├── tool // 工具脚本
│   │   │   └── test.php
│   │   └── worker // 异步处理worker
│   │       ├── Bootstrap.php // worker入口文件
│   │       └── TestJob1Worker.php
│   └── www
│       ├── favicon.ico
│       └── index.php // http 入口文件
├── test // 测试用例
│   ├── bootstrap.php
│   ├── dao
│   │   └── DAOUserAddressTest.php
│   ├── lib
│   │   └── TokenTest.php
│   └── model
│       ├── FollowTest.php
│       └── StubTest.php
└── vendor // 依赖库
    ├── autoload.php
    ├── composer
    │   ├── ClassLoader.php
    │   ├── LICENSE
    │   ├── autoload_classmap.php
    │   ├── autoload_files.php
    │   ├── autoload_namespaces.php
    │   ├── autoload_psr4.php
    │   ├── autoload_real.php
    │   ├── autoload_static.php
    │   └── installed.json
    └── pepper
        ├── lib  // 通用类库
        │   ├── .gitignore
        │   ├── .gitlab-ci.yml
        │   ├── composer.json
        │   ├── phpunit.xml
        │   ├── src
        │   │   ├── ArrayHelper.php
        │   │   ├── BaseConvert.php
        │   │   ├── Cache.php
        │   │   ├── Cached.php
        │   │   ├── Curl.php
        │   │   ├── ES.php
        │   │   ├── Feature.php
        │   │   ├── Geo.php
        │   │   ├── ICache.php
        │   │   ├── Lock.php
        │   │   ├── Logger.php
        │   │   ├── MCode.php
        │   │   ├── Random.php
        │   │   ├── RealRedisProxy.php
        │   │   ├── SimpleConfig.php
        │   │   ├── StringHelper.php
        │   │   ├── Ticket.php
        │   │   ├── Timer.php
        │   │   ├── Translate.php
        │   │   ├── Util.php
        │   │   └── helper.php
        ├── process // worker库
        │   ├── .gitignore
        │   ├── .gitlab-ci.yml
        │   ├── README.md
        │   ├── composer.json
        │   ├── config
        │   │   ├── process_conf.php.release
        │   │   └── process_conf.php.test
        │   ├── phpunit.xml
        │   ├── src
        │   │   ├── Base
        │   │   │   ├── Job.php
        │   │   │   ├── Queue.php
        │   │   │   └── QueueException.php
        │   │   └── ProcessClient.php
        │   ├── tests
        │   │   ├── TestWorker.php
        │   │   ├── addTaskTest.php
        │   │   ├── addWorkerTest.php
        │   │   ├── config
        │   │   │   └── process_conf.php.test.mode
        │   │   ├── delayTest.php
        │   │   ├── getTaskCountTest.php
        │   │   ├── init.php
        │   │   ├── priorityTest.php
        │   │   ├── rescueTest.php
        │   │   └── worker.php
        │   └── tools
        │       └── rescue.php
        └── qframedb // 数据库
            ├── .gitignore
            ├── composer.json
            ├── composer.lock
            ├── phpunit.xml
            ├── src
            │   ├── QFrameDB.php
            │   ├── QFrameDBException.php
            │   ├── QFrameDBPDO.php
            │   └── QFrameDBStatment.php
            └── tests
                ├── QFrameDBTest.php
                └── init.php
```                
### 支持http方式，简单实现了MVC
```
    1. 入口www/index.php
    2. 路由，接口控制在BaseController.php中实现
    3. 接口权限控制在server.common.php中{$AUTH_CONF}配置
    4. 接口定义返回状态码在server.common.php中配置
    5. 参考FollowController.php实现controller层
    6. 参考DAOFollowing.php 实现数据库操作层
    7. 参考Follow.php 实现Model层
```
### 支持异步任务方式，入口process/worker/Bootstrap.php
```
    1. 异步数据写入参考接口：test/worker
    2. 消费处理参考： process/worker/TestJob1Worker.php
    3. 需要在定义process.php.xxx 定义key, 指定队列
    4. /usr/local/bin/php /home/q/system/pepper/framework/front/src/process/worker/Bootstrap.php start test_job1 启动
```

### 支持脚本方式，tool，cron脚本目录
```
    参考 process/tool/TestTool.php 实现
```

# Nacos SDK for PHP

<a href="https://packagist.org/packages/verystar/nacos-php-sdk"><img src="https://poser.pugx.org/verystar/nacos-php-sdk/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/verystar/nacos-php-sdk"><img src="https://poser.pugx.org/verystar/nacos-php-sdk/v/stable.svg" alt="Latest Stable Version"></a>

Nacos SDK for PHP

> 基于 Nacos 3.x 接口：登录 `/nacos/v3/auth/user/login`，获取配置 `/nacos/v3/client/cs/config`，发布配置 `/nacos/v3/admin/cs/config`

## Install

```
composer require verystar/nacos-php-sdk
```

Or add a dependency to the composer.json

```
"require": {
    "verystar/nacos-php-sdk": "1.1.*"
}
```

Run

```
composer update
```

## Usage

```php
use Very\Nacos\Client;

$client = new Client([
    "username"=>"***********",
    "password"=>"***********",
    "server_addr"=>"https://test.nacos.com",
    "namespace"=>"***********",
]);


//get config
$ret = $client->getConfig("test","DEFAULT_GROUP");
print_r($ret);

//publish config (need admin permission)
$client->publishConfig("test","hello verystar","DEFAULT_GROUP");

//save config if config file not exists
$client->saveConfig("test","DEFAULT_GROUP","./config/db.php");
```

> accessToken 会自动缓存并在过期前重新登录，无需手动处理；未配置 `username`/`password` 时按匿名方式访问（适用于未开启鉴权的 Nacos）。

## Exception

if throw NacosException,the fetch configuration failed

## License

The SDK is open-sourced software licensed under the MIT license.

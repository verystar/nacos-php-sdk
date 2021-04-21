# Nacos SDK for PHP
<a href="https://packagist.org/packages/verystar/nacos-php-sdk"><img src="https://poser.pugx.org/verystar/nacos-php-sdk/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/verystar/nacos-php-sdk"><img src="https://poser.pugx.org/verystar/nacos-php-sdk/v/stable.svg" alt="Latest Stable Version"></a>

Aliyun Nacos SDK for PHP

## Install

```
composer require verystar/nacos-php-sdk
```

Or add a dependency to the composer.json

```
"require": {
    "verystar/nacos-php-sdk": "1.0.*"
}
```

Run
```
composer update
```

## Usage

```php
use Aliyun\ACM\Client;

$client = new Client([
    "username"=>"***********",
    "password"=>"***********",
    "server_addr"=>"https://test.nacos.com",
    "namespace"=>"***********",
]);


//get config
$ret = $client->getConfig("test","DEFAULT_GROUP");
print_r($ret);

//save config if config file not exists 
$client->saveConfig("test","DEFAULT_GROUP","./config/db.php");
```

## Exception
if throw NacosException,the fetch configuration failed

## License
The SDK is open-sourced software licensed under the MIT license.

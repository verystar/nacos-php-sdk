<?php
/**
 * Created by PhpStorm.
 * User: fifsky@gmail.com
 * Date: 2018/7/4 3:43 PM
 */

namespace Very\Tests;

use PHPUnit\Framework\TestCase;
use Very\Nacos\Client;

class ClientTest extends TestCase
{
    private $group = "DEFAULT_GROUP";
    private $namespace = "0a299fd7-5c7b-4762-ab45-8e48a70cb4fe";

    /**
     * @return Client
     */
    private function getClient()
    {
        $username = getenv("NACOS_USERNAME");
        $password = getenv("NACOS_PASSWORD");

        if (!$username || !$password) {
            $this->markTestSkipped("NACOS_USERNAME or NACOS_PASSWORD not set");
        }

        $serverAddr = getenv("NACOS_SERVER_ADDR");
        $namespace  = getenv("NACOS_NAMESPACE");

        return new Client([
            "username"    => $username,
            "password"    => $password,
            "server_addr" => $serverAddr ? $serverAddr : "https://nacos.verystar.net",
            "namespace"   => $namespace ? $namespace : $this->namespace,
        ]);
    }

    public function testGetConfig()
    {
        $ret = $this->getClient()->getConfig("test", $this->group);
        $this->assertEquals("hello verystar", $ret);
    }

    public function testPublishConfig()
    {
        $dataId  = "php-sdk-test";
        $content = "hello verystar " . date("Y-m-d H:i:s");

        $this->assertTrue($this->getClient()->publishConfig($dataId, $content, $this->group));
        $this->assertEquals($content, $this->getClient()->getConfig($dataId, $this->group));
    }

    public function testSaveConfig()
    {
        $filepath = sys_get_temp_dir() . "/nacos_test_config.php";
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        $this->getClient()->saveConfig("test", $this->group, $filepath);

        $this->assertFileExists($filepath);
        $this->assertEquals("hello verystar", file_get_contents($filepath));

        unlink($filepath);
    }
}

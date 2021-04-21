<?php
/**
 * Created by PhpStorm.
 * User: fifsky@gmail.com
 * Date: 2018/7/4 3:43 PM
 */

namespace Very\Tests;

use Very\Nacos\RequestException;
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
        return new Client([
            "username" => getenv("NACOS_USERNAME"),
            "password" => getenv("NACOS_PASSWORD"),
            "server_addr"  => "https://nacos.verystar.net",
            "namespace" => $this->namespace,
        ]);
    }


    public function testGetConfig()
    {
        $ret = $this->getClient()->getConfig("test", $this->group);
        $this->assertEquals($ret, "hello verystar");
    }

    public function testSubscribe()
    {
        $this->getClient()->saveConfig("test", $this->group,"./testconfig.php");
        $ret = file_get_contents("./testconfig.php");
        $this->assertEquals($ret, "hello verystar");
    }
}
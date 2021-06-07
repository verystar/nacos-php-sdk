<?php
/**
 * Created by PhpStorm.
 * User: fifsky@gmail.com
 * Date: 2018/7/3 10:28 PM
 */

namespace Very\Nacos;

use Very\Nacos\Exceptions\NacosException;
use Very\Nacos\Exceptions\RequestException;

class Client
{

    protected $username;
    protected $password;
    protected $server_addr;
    protected $nameSpace;
    protected $request;
    protected $accessToken;

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->username      = isset($config['username']) ? $config['username'] : "";
        $this->password      = isset($config['password']) ? $config['password'] : "";
        $this->server_addr   = isset($config['server_addr']) ? $config['server_addr'] : "";
        $this->nameSpace     = isset($config['namespace']) ? $config['namespace'] : "";
        $this->request       = new Request();
    }


    private function initServer()
    {
        $data = [
            "username" => $this->username,
            "password" => $this->password,
        ];

        $ret = $this->request->post($this->server_addr . "/nacos/v1/auth/login", $data);

//        print_r($ret->debug());

        if ($ret->getError()) {
            throw new NacosException("nacos login error," . $ret->getError());
        }

        $info = json_decode($ret->getBody(), true);

        if (!isset($info["accessToken"])) {
            throw new NacosException("nacos login error," . $ret->getBody());
        }

        $this->accessToken = $info["accessToken"];
    }

    /**
     * @param        $api
     * @param array  $params
     * @param string $method
     *
     * @return string
     * @throws RequestException
     */
    private function callApi($api, $params = [], $method = "GET")
    {
        if (!$this->server_addr || !$this->accessToken) {
            throw new RequestException("nacos init accessToken error");
        }
        $params["accessToken"] = $this->accessToken;


        if ($method == "GET") {
            $spec = strpos($api, "?") === false ? "?" : "&";
            $ret  =
                $this->request->get(sprintf("%s%s%s%s", $this->server_addr, $api, $spec, http_build_query($params)));
        } else {
            $ret =
                $this->request->post($this->server_addr . $api, $params);
        }

        if ($ret->getError()) {
            throw new RequestException("request error:" . $ret->getError());
        }

        if ($ret->getStatusCode() != 200) {
            throw new RequestException("response error:" . $ret->getBody());
        }

        return $ret->getBody();
    }

    /**
     * @param        $dataId
     * @param string $group
     *
     * @return string
     * @throws RequestException
     */
    public function getConfig($dataId, $group = "DEFAULT_GROUP")
    {
        $this->initServer();
        return $this->callApi("/nacos/v1/cs/configs", [
            "tenant" => $this->nameSpace,
            "dataId" => $dataId,
            "group"  => $group,
        ]);
    }

    /**
     * 保存配置到目标文件
     *
     * @param string $dataId
     * @param string $group
     * @param string $filepath
     *
     * @throws RequestException
     */
    public function saveConfig($dataId, $group, $filepath = "")
    {
        if (!file_exists($filepath)) {
            $ret = $this->getConfig($dataId, $group);
            file_put_contents($filepath, $ret, LOCK_EX);
        }
    }
}

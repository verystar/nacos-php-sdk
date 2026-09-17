<?php
/**
 * Created by PhpStorm.
 * User: fifsky@gmail.com
 * Date: 2018/7/3 10:28 PM
 */

namespace Very\Nacos;

use Very\Nacos\Exceptions\NacosException;
use Very\Nacos\Exceptions\RequestException;

/**
 * Nacos 3.x 客户端
 *
 * 使用标准 Nacos v3 接口：
 *  - 登录：POST /nacos/v3/auth/user/login
 *  - 获取配置：GET /nacos/v3/client/cs/config
 *  - 发布配置：POST /nacos/v3/admin/cs/config（v3 client 接口不提供发布能力，需使用 admin 接口）
 */
class Client
{
    /** 登录接口 */
    const API_LOGIN = "/nacos/v3/auth/user/login";

    /** 获取配置接口（v3 client） */
    const API_CONFIG_GET = "/nacos/v3/client/cs/config";

    /** 发布配置接口（v3 admin） */
    const API_CONFIG_PUBLISH = "/nacos/v3/admin/cs/config";

    /** accessToken 提前过期时间（秒），避免请求过程中 token 失效 */
    const TOKEN_EXPIRE_AHEAD = 600;

    /** 服务端未返回 tokenTtl 时 accessToken 的默认有效期（秒） */
    const DEFAULT_TOKEN_TTL = 18000;

    protected $username;
    protected $password;
    protected $server_addr;
    protected $namespace;
    protected $request;

    protected $accessToken;
    protected $tokenExpireAt = 0;

    /**
     * Client constructor.
     *
     * @param array $config 支持 username、password、server_addr、namespace
     */
    public function __construct($config = [])
    {
        $this->username    = isset($config['username']) ? $config['username'] : "";
        $this->password    = isset($config['password']) ? $config['password'] : "";
        $this->server_addr = isset($config['server_addr']) ? rtrim($config['server_addr'], "/") : "";
        $this->namespace   = isset($config['namespace']) ? $config['namespace'] : "";
        $this->request     = new Request();
    }

    /**
     * 登录获取 accessToken（v3 接口），并在本地缓存有效期
     *
     * @throws NacosException
     */
    private function login()
    {
        $ret = $this->request->post($this->server_addr . self::API_LOGIN, [
            "username" => $this->username,
            "password" => $this->password,
        ]);

        if ($ret->getError()) {
            throw new NacosException("nacos login error:" . $ret->getErrorMessage());
        }

        if ($ret->getStatusCode() != 200) {
            throw new NacosException("nacos login error:" . $ret->getBody());
        }

        $info = json_decode($ret->getBody(), true);

        if (!is_array($info) || !isset($info["accessToken"])) {
            throw new NacosException("nacos login error:" . $ret->getBody());
        }

        $ttl = isset($info["tokenTtl"]) ? intval($info["tokenTtl"]) : self::DEFAULT_TOKEN_TTL;
        if ($ttl <= 0) {
            $ttl = self::DEFAULT_TOKEN_TTL;
        }

        $expire = $ttl - self::TOKEN_EXPIRE_AHEAD;
        if ($expire <= 0) {
            $expire = intval($ttl / 2);
        }

        $this->accessToken   = $info["accessToken"];
        $this->tokenExpireAt = time() + $expire;
    }

    /**
     * 获取 accessToken，未配置账号密码时返回空字符串（服务端未开启鉴权时可直接访问）
     *
     * @return string
     * @throws NacosException
     */
    private function getAccessToken()
    {
        if ($this->username === "" && $this->password === "") {
            return "";
        }

        if ($this->accessToken && time() < $this->tokenExpireAt) {
            return $this->accessToken;
        }

        $this->login();

        return $this->accessToken;
    }

    /**
     * 调用 v3 接口
     *
     * @param string $api
     * @param array  $params
     * @param string $method
     *
     * @return string
     * @throws RequestException
     * @throws NacosException
     */
    private function callApi($api, $params = [], $method = "GET")
    {
        if (!$this->server_addr) {
            throw new RequestException("nacos server_addr is empty");
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken !== "") {
            $params["accessToken"] = $accessToken;
        }

        if ($method == "GET") {
            $spec = strpos($api, "?") === false ? "?" : "&";
            $ret  =
                $this->request->get(sprintf("%s%s%s%s", $this->server_addr, $api, $spec, http_build_query($params)));
        } else {
            $ret = $this->request->post($this->server_addr . $api, $params);
        }

        if ($ret->getError()) {
            throw new RequestException("request error:" . $ret->getErrorMessage());
        }

        if ($ret->getStatusCode() != 200) {
            throw new RequestException("response error:" . $ret->getBody());
        }

        return $ret->getBody();
    }

    /**
     * 解析 v3 统一返回结构：{"code":0,"message":"success","data":...}
     *
     * @param string $body
     * @param string $action
     *
     * @return mixed
     * @throws RequestException
     */
    private function parseResponse($body, $action)
    {
        $ret = json_decode($body, true);

        if (!is_array($ret) || !array_key_exists("code", $ret)) {
            throw new RequestException(sprintf("nacos %s fail:%s", $action, $body));
        }

        if ($ret["code"] != 0) {
            $message = isset($ret["message"]) ? $ret["message"] : $body;
            throw new RequestException(sprintf("nacos %s fail:%s", $action, $message));
        }

        return isset($ret["data"]) ? $ret["data"] : null;
    }

    /**
     * 获取配置（v3 client 接口）
     *
     * @param string $dataId
     * @param string $group
     *
     * @return string
     * @throws RequestException
     * @throws NacosException
     */
    public function getConfig($dataId, $group = "DEFAULT_GROUP")
    {
        $body = $this->callApi(self::API_CONFIG_GET, [
            "namespaceId" => $this->namespace,
            "groupName"   => $group,
            "dataId"      => $dataId,
        ]);

        $data = $this->parseResponse($body, "get config");

        if (!is_array($data) || !isset($data["content"])) {
            throw new RequestException("nacos get config fail:" . $body);
        }

        return $data["content"];
    }

    /**
     * 发布配置（v3 admin 接口，需要管理员权限）
     *
     * @param string $dataId
     * @param string $content
     * @param string $group
     *
     * @return bool
     * @throws RequestException
     * @throws NacosException
     */
    public function publishConfig($dataId, $content, $group = "DEFAULT_GROUP")
    {
        $body = $this->callApi(self::API_CONFIG_PUBLISH, [
            "namespaceId" => $this->namespace,
            "groupName"   => $group,
            "dataId"      => $dataId,
            "content"     => $content,
        ], "POST");

        $this->parseResponse($body, "publish config");

        return true;
    }

    /**
     * 保存配置到目标文件
     *
     * @param string $dataId
     * @param string $group
     * @param string $filepath
     *
     * @throws RequestException
     * @throws NacosException
     */
    public function saveConfig($dataId, $group, $filepath = "")
    {
        if (!file_exists($filepath)) {
            $ret = $this->getConfig($dataId, $group);
            file_put_contents($filepath, $ret, LOCK_EX);
        }
    }
}

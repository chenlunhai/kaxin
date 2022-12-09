<?php
/**
 * Created by PhpStorm.
 * User: leilei.yang
 * Date: 2021/4/20
 * Time: 13:34
 */

namespace AdaPaySdk;
use AdaPay\AdaPay;


class AdaPayCommon extends AdaPay
{
    function packageRequestUrl($requestParams=array()) {
        $adapayFuncCode = $requestParams["adapay_func_code"];
        if (empty($adapayFuncCode)){
            try {
                throw new \Exception('adapay_func_code不能为空');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        $adapayApiVersion = isset($requestParams['adapay_api_version']) ? $requestParams['adapay_api_version'] : 'v1';

        self::getGateWayUrl(self::$gateWayType);
        return  self::$gateWayUrl . "/" . $adapayApiVersion . "/" . str_replace(".", "/",$adapayFuncCode);
    }

    /**
     * 通用请求接口 - POST - 多商户模式
     * @param array $params 请求参数
     * @param string $merchantKey 如果传了则为多商户，否则为单商户
     */
    public function requestAdapay($params=array(), $merchantKey="") {
        if (!empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        $request_params = $params;
        $req_url =  $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }


    /**
     * 通用请求接口 - POST - 多商户模式
     * @param array $params
     * @param $merchantKey
     */
    public function requestAdapayUits($params=array(), $merchantKey="") {
        self::$gateWayType = "page";

        if (!empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        $request_params = $params;
        $req_url =  $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        echo $req_url;

        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    /**
     * 通用查询接口 - GET
     * @param array $params
     * @param string $merchantKey 传了则为多商户模式
     */
    public function queryAdapay($params=array(), $merchantKey="") {
        if (!empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        ksort($params);
        $request_params = $params;
        $req_url =  $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header =  $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function queryAdapayUits($params=array(), $merchantKey="") {
        self::$gateWayType = "page";

        if (!empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }
        ksort($params);
        $request_params = $params;
        $req_url =  $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header =  $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    function array_remove($arr, $key) {
        if(!array_key_exists($key, $arr)){
            return $arr;
        }

        $keys = array_keys($arr);
        $index = array_search($key, $keys);

        if($index !== FALSE){
            array_splice($arr, $index, 1);
        }

        return $arr;
    }

    function format_request_params($request_params) {
        $request_params = $this->array_remove($request_params, "adapay_func_code");
        $request_params = $this->array_remove($request_params, "adapay_api_version");
        $request_params = $this->do_empty_data($request_params);
        return $request_params;
    }
}
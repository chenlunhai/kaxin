<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class Member extends AdaPay
{
    static private $instance;

    public $endpoint = "/v1/members";
    public $customer = NULL;

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    //=============个人用户

    public function create($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  $this->gateWayUrl . $this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint);
    }

    public function query($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url =  $this->gateWayUrl . $this->endpoint . "/" . $request_params['member_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint. "/" . $params['member_id']);
    }

    public function update($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  $this->gateWayUrl . $this->endpoint . '/update';
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint."/update");
    }

    public function queryList($params=array()){
        $request_params = $params;
        $req_url =  $this->gateWayUrl . $this->endpoint . "/list";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/list");
    }

}
<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class AdapayTools extends AdaPay
{
    static private $instance;

    public $endpoint = "/v1/bill/download";
    public $union_endpoint = "/v1/union/user_identity";
    // public $billDownload = NULL;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function download($bill_date) {
        $params['bill_date'] = $bill_date;
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl . $this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, AdaPay::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint);
    }

    public function unionUserId($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  $this->gateWayUrl . $this->union_endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->union_endpoint);
    }

    /**
     * HTTP 验签
     * @Author   Kelly
     * @DateTime 2020-10-23
     * @version  V1.1.4
     * @param    array 参数
     * @return   logic true/false
     */
    public function verifySign($params_str="", $sign=""){
        return $this->ada_tools->verifySign($sign, $params_str);
    }


}
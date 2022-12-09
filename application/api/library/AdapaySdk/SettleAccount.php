<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class SettleAccount extends AdaPay
{
    static private $instance;

    public $endpoint = "/v1/settle_accounts";
    public $cash_endpoint = "/v1/cashs";
    public $settle = NULL;

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }

    /**
     * 查询账户余额
     * @Author   Kelly
     * @DateTime 2020-10-23
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function balance($params=array()){
        ksort($params);
        $request_params = $this->do_empty_data($params);
        $req_url =  $this->gateWayType . $this->endpoint . "/balance";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result =  $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/balance");
    }

    public function create($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint);
    }

    public function query($params=array()){
        $request_params = $params;
        $settle_account_id = isset($params['settle_account_id']) ? $params['settle_account_id']: '';
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/" . $settle_account_id;
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/" . $settle_account_id);
    }

    public function delete($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/delete";
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint."/delete");
    }

    public function detail($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/settle_details";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/settle_details");
    }

    public function update($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/modify";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint."/modify");
    }

    
}
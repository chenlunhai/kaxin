<?php
namespace AdaPaySdk;
use AdaPay\AdaPay;

class CorpMember extends AdaPay
{
    static private $instance;

    public $endpoint = "/v1/corp_members";
    public $corp = NULL;

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }


    public function create($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint;
        ksort($request_params);
        $sign_request_params = $request_params;
        unset($sign_request_params['attach_file']);
        ksort($sign_request_params);
        $sign_str = $this->ada_tools->createLinkstring($sign_request_params);

        $header =  $this->get_request_header($req_url, $sign_str, self::$headerEmpty);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header);
    }

    public function update($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/update";
        ksort($request_params);
        $sign_request_params = $request_params;
        unset($sign_request_params['attach_file']);
        ksort($sign_request_params);
        $sign_str = $this->ada_tools->createLinkstring($sign_request_params);

        $header =  $this->get_request_header($req_url, $sign_str, self::$headerEmpty);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header);
    }


    public function query($params=array()){
        ksort($params);
        $request_params = $this->do_empty_data($params);
        $req_url =  $this->gateWayUrl . $this->endpoint. "/" . $params['member_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }
}
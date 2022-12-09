<?php
namespace AdaPaySdk;
use AdaPay\AdaPay;

class PaymentConfirm extends AdaPay
{
    static private $instance;

    public $endpoint = "/v1/payments";

    public function __construct()
    {
        parent::__construct();
    }

    //=============支付确认对象
    /**
     * 创建支付确认对象
     * @Author   Kelly
     * @DateTime 2020-10-22
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function create($params=array()){
        $request_params = $this->do_empty_data($params);
        $req_url =  $this->gateWayUrl .$this->endpoint."/confirm";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result =  $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint."/confirm");
    }

    /**
     * 查询支付确认对象
     * @Author   Kelly
     * @DateTime 2020-10-22
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function query($params=array()){
        $request_params = $params;
        $req_url =  $this->gateWayUrl . $this->endpoint ."/confirm/" . $params['payment_confirm_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/confirm/" . $params['payment_confirm_id']);
    }

    /**
     * 查询支付确认对象列表
     * @Author   Kelly
     * @DateTime 2020-10-22
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function queryList($params=array()){
        ksort($params);
        $request_params = $this->do_empty_data($params);
        $req_url =  $this->gateWayUrl . $this->endpoint."/confirm/list" ;
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/confirm/list");
    }
}
<?php
namespace AdaPaySdk;
use AdaPay\AdaPay;


class Drawcash extends AdaPay
{
    static private $instance;

    public $refundOrder = NULL;
    public $refundOrderQuery = NULL;
    public $endpoint = "/v1/cashs";

    public function __construct()
    {
        parent::__construct();
        // $this->sdk_tools = SDKTools::getInstance();
    }


    //=============取现对象

    /**
     * 创建取现对象
     * @Author   Kelly
     * @DateTime 2020-10-23
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function create($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
        // $this->result = $this->sdk_tools->post($params, $this->endpoint);
    }

    /**
     * 查询取现对象
     * @Author   Kelly
     * @DateTime 2020-10-23
     * @version  V1.1.4
     * @param    array
     * @return   array
     */
    public function query($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = $this->gateWayUrl.$this->endpoint."/stat";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
        // $this->result = $this->sdk_tools->get($params, $this->endpoint."/stat");
    }


    
}
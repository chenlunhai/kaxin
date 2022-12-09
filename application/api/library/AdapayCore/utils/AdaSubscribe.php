<?php
namespace AdaPay;
use Workerman\Worker;
use Workerman\Mqtt\Client;

class AdaSubscribe extends AdaPay
{
    public $worker;
    public $username;
    public $password;
    public $accessKey = "";
    public $instanceId = "";
    public $groupId = "";
    public $topic = "";
    public $clientId = "";
    public $client_address = "";
    public $token = "";
    public $callbackFunc = "";
    public $mq_token = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->_init();
        $this->mq_token = new AdaMqttToken();
    }

    public function workerStart($workerMsg, $callback, $apiKey="", $client_id=""){
        $this->worker = new Worker();
        $this->_setting($apiKey, $client_id);
        $topic = $this->topic;
        $this->worker->onWorkerStart =  function () use ($topic,  $workerMsg, $callback){
            $options = array(
                'keepalive'=> 5,
                'username'=>$this->username,
                'password'=>$this->_get_password(),
                'client_id'=> $this->clientId,
                'clean_session'=> false,
                'debug'=>self::$isDebug
            );

            $client = new Client('mqtt://'.$this->client_address, $options);
            $client->onConnect = function($client) use ($topic) {
                $client->subscribe($topic, ['qos'=>1]);
            };
            $client->onError = function ($exception) use($options, $client){
                $this->worker->stopAll();
                print("execute before password:---------------------------");
                var_dump($options['password']);
                $options['password'] = $this->_get_password(); //重新获取token
                print("execute after password:---------------------------");
                var_dump($options['password']);
                $client->onConnectionClose(); //断开重新连接

            };
            $client->onMessage = function($topic, $content) use ($options, $workerMsg, $callback, $client) {
                if( $topic == '$SYS/tokenExpireNotice' ){
                    print("execute before password:---------------------------");
                    var_dump($options['password']);
                    $options['password'] = $this->_get_password(); //重新获取token
                    print("execute OnMessage password:---------------------------");
                    var_dump($options['password']);
                    $client->onConnectionClose(); //断开重新连接
                }else{
                    call_user_func(array($workerMsg, 'mqttCallBack'), $content, $callback, $topic);
                }

            };
            $client->connect();
        };
        $this->worker->runAll();
    }

    public  function mqttCallBack($content, $callback, $topic){
        $callback($content, $topic);
    }

    private function _init(){
        $this->accessKey = self::$mqttAccessKey;
        $this->instanceId = self::$mqttInstanceId;
        $this->groupId = self::$mqttGroupId;
        $this->client_address = self::$mqttAddress;
    }

    private function _get_password(){
        $token = $this->mq_token->getToken();
        return "R|".$token;
    }

    private function _setting($apiKey, $client_id){
        $_apiKey = empty($apiKey)? parent::$api_key: $apiKey;
        $client_id =  empty($client_id)? $_apiKey: $_apiKey.$client_id;
        $this->username = 'Token|' . $this->accessKey . '|' . $this->instanceId;
        $this->clientId = $this->groupId . '@@@' . md5($client_id);
        $this->topic = "topic_crhs_sender/".$_apiKey;
    }

}

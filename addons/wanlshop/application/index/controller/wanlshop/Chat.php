<?php
namespace app\index\controller\wanlshop;

use app\common\controller\Wanlshop;
use addons\wanlshop\library\WanlChat\WanlChat;
use think\Config;
use think\Db;

/**
 * 主页
 * @internal
 */
class Chat extends Wanlshop
{
    protected $noNeedLogin = '';
    protected $noNeedRight = '*';
    
    public function _initialize()
    {
        parent::_initialize();
		$this->model = new \app\index\model\wanlshop\Chat;
		$this->wanlchat = new WanlChat();
    }
    
	/**
	 * 即时通讯绑定client_id 1.0.2升级
	 */
	public function chatbind()
	{
	    //设置过滤方法
	    $this->request->filter(['strip_tags']);
	    if ($this->request->isAjax()) {
	        $client_id = $this->request->post('client_id');
	        $client_id ? '' : ($this->error(__('Invalid parameters')));
	        $user_id = $this->auth->id;
			$this->wanlchat->bind($client_id, $user_id);
	        // 查询是否有离线消息 1.0.2升级 弃用貌似意义不大
	   //      $list = $this->model
	   //          ->where(['to_id' => $user_id, 'online' => 0, 'type' => 'chat'])
	   //          ->whereTime('createtime', 'week')
	   //          ->field('id,form_uid,to_id,form,message,type,online,createtime')
	   //          ->select();
	   //      foreach ($list as $row) {
	   //          $this->wanlchat->send($user_id, $row);
				// $this->model->save(['online' => 1], ['id' => $row['id']]);
	   //      }
	        $this->success(__('绑定成功'), null, $this->wanlchat->isOnline($user_id));
	    }
	}
	
	
	/**
	 * 全部消息列表 1.0.2升级
	 */
	public function lists()
	{
		if(!$this->wanlchat->isWsStart()){
			$this->error('请启动IM即时通讯服务');
		}
		//设置过滤方法
		$this->request->filter(['strip_tags']);
		if ($this->request->isAjax()) {
		    $uid = $this->auth->id;
		    $formlist = [];
		    $tolist = [];
		    $chatModel = $this->model;
		    $history = $chatModel->where("(form_uid={$uid} or to_id={$uid}) and type='chat'")
		        ->order('createtime esc')
		        ->select();
		    foreach (collection($history)->toArray() as $vo) {
		        if ($vo['form_uid'] == $uid) {
		            $formlist[] = $vo['to_id'];
		        }
		        if ($vo['to_id'] == $uid) {
		            $tolist[] = $vo['form_uid'];
		        }
		    }
		    $list = model('app\common\model\User')
		        ->where('id', 'in', array_unique(array_merge($formlist, $tolist)))
		        ->field('id,username,nickname,avatar')
		        ->select();
			$chat = [];
		    $message = [];
			$datetime = [];
			$countNum = 0;
		    foreach (collection($list)->toArray() as $user) {
		        //查询店铺为读消息
		        $count = $chatModel
		            ->where(['form_uid' => $user['id'], 'to_id' => $uid, 'isread' => 0])
		            ->count();
		        //查询和店铺最新消息
		        $content = $chatModel->where("((form_uid={$uid} and to_id={$user['id']}) or (form_uid={$user['id']} and to_id={$uid})) and type='chat'")
		            ->order('createtime desc')
		            ->limit(1)
		            ->find();
		        //转换图片类型
				if($content['message']['type'] == 'img'){
					$msgtext = '[图片消息]';
				}else if($content['message']['type'] == 'voice'){
					$msgtext = '[语音消息]';
				}else if($content['message']['type'] == 'goods'){
					$msgtext = '[商品消息]';
				}else if($content['message']['type'] == 'order'){
					$msgtext = '[订单消息]';
				}else if($content['message']['type'] == 'text'){
					$msgtext = $content['message']['content']['text'];
				}else{
					$msgtext = '[未知消息类型]';
				}
		        //整理输出
		        $chat[] = [
		            'user_id' => $user['id'],
		            'nickname' => $user['nickname'],
		            'avatar' => $user['avatar'],
		            'content' => $msgtext,
		            'count' => $count,
					'createtime' => $content['createtime'],
					'isOnline' => $this->wanlchat->isOnline($user['id'])
		        ];
				// 时间排序
				$datetime[] = $content['createtime'];
		    }
			if($datetime){
				array_multisort($datetime, SORT_DESC, $chat);
			}
		    $this->success("拉取成功", null, [
				'chat' => $chat,
				'shop' => [
					'id' => $this->shop->id,
					'user_id' => $this->shop->user_id,
					'avatar' => $this->shop->avatar,
					'shopname' => $this->shop->shopname
				]
			]);
		}
	}
	
	/**
	 * 历史消息记录 1.0.2升级
	 */
	public function history()
	{
	    //设置过滤方法
	    $this->request->filter(['strip_tags']);
	    if ($this->request->isAjax()) {
	        $id = $this->request->post('id');
	        $id?'':($this->error(__('Invalid parameters')));
	        $uid = $this->auth->id;
	        // 设置成已读
	        $this->model
	            ->where(['form_uid' => $id, 'to_id' => $uid, 'isread' => 0])
	            ->update(['isread' => 1]);
	        $chat = $this->model
	            ->where("((form_uid={$uid} and to_id={$id}) or (form_uid={$id} and to_id={$uid})) and type='chat'")
	            // ->whereTime('createtime', 'month')
	            ->order('createtime esc')
	            ->limit(500) //最多拉取500条，迭代版本做分页
	            ->select();
	        $this->success("成功", null, [
	            'chat' => $chat,
	            'isOnline' => $this->wanlchat->isOnline($id)
	        ]);
	    }
	}
	
	/**
	 * 全部已读
	 */
	public function read()
	{
	    //设置过滤方法
	    $this->request->filter(['strip_tags']);
	    if ($this->request->isAjax()) {
	        $id = $this->request->post('id');
	        $id?'':($this->error(__('Invalid parameters')));
	        $uid = $this->auth->id;
	        // 设置成已读
	        $this->model
	            ->where(['form_uid' => $id, 'to_id' => $uid, 'isread' => 0])
	            ->update(['isread' => 1]);
	        $this->success(__('全部已读'));
	    }
	}
    
    /**
     * 发送消息
     */
    public function chatSend()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $message = $this->request->post();
            $message['form']['id'] = $this->auth->id;
			$message['form']['shop_id'] = $this->shop->id;
            // 未来增加权限判断
            // 查询是否在线
			$online = $this->wanlchat->isOnline($message['to_id']);
            // 保存聊天记录到服务器
            $data = $this->model;
            $data->form_uid = $message['form']['id'];
            $data->to_id = $message['to_id'];
            $data->form = json_encode($message['form']);
            $data->message = json_encode($message['message']);
            $data->type = $message['type'];
            $data->online = $online;
            $data->save();
            $message['id'] = $data->id;
            // 在线发送
            $online == 1 ? ($this->wanlchat->send($message['to_id'], $message)) : '';
            $this->success(__('发送成功'));
        }
    }
}

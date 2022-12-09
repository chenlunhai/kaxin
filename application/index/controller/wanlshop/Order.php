<?php

// 2020年2月17日22:05:38

namespace app\index\controller\wanlshop;

use addons\wanlshop\library\WanlChat\WanlChat;
use app\common\controller\Wanlshop;
use addons\wanlshop\library\Ehund; //快递100订阅
use think\Db;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 * .20210309
 * 
 */
class Order extends Wanlshop {

    protected $noNeedLogin = '';
    protected $noNeedRight = '*';

    /**
     * Order模型对象
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\index\model\wanlshop\Order;
        $kuaidi = new \app\index\model\wanlshop\Kuaidi;
        $this->wanlchat = new WanlChat();
        $this->view->assign("kuaidiList", $kuaidi->field('name,code')->select());
        $this->view->assign("stateList", $this->model->getStateList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("statesList", $this->model->getStatesList());
    }

    public function exportorder() {
        if ($this->request->isAjax()) {

            $starttime = $this->request->get('start');
            $end = strtotime($this->request->get('end'));
            $start = strtotime($starttime);
            $where[] = ['createtime', 'between time', [$start, $end]];
            $list = \think\Db::name("wanlshop_order")->where(["state" => '1'])->whereBetween('createtime', [$start, $end])->order("createtime desc")->select();
            foreach ($list as $key => $value) {

                $user = \think\Db::name("user")->where(["id" => $value["user_id"]])->find();
                $list[$key]["user"] = $user;
                $pay = \think\Db::name("wanlshop_pay")->where(["user_id" => $value["user_id"], "order_id" => $value["id"]])->find();

                if ($pay["pay_type"] == '0') {
                    $pay["pay_type_text"] = '余额支付';
                } elseif ($pay["pay_type"] == '1') {
                    $pay["pay_type_text"] = '微信支付';
                } else {
                    $pay["pay_type_text"] = '支付宝支付#0';
                }
                //支付类型:0=余额支付,1=微信支付,2=支付宝支付
                $list[$key]["pay"] = $pay;
                $list[$key]["ordergoods"] = \think\Db::name("wanlshop_order_goods")->where(["order_id" => $value["id"]])->select();
//                $row->getRelation('user')->visible(['id', 'username', 'nickname', 'avatar']);
//                $row->getRelation('pay')->visible(['pay_no', 'price', 'order_price', 'freight_price', 'discount_price', 'actual_payment']);
                $addressInfo = \think\Db::name("wanlshop_order_address")->where(["order_id" => $value["id"]])->find();
                if ($addressInfo) {
                    $list[$key]["AddressInfo"] = $addressInfo;
                }
            }
            $list = collection($list)->toArray();
            $result = array("rows" => $list);
            return json($result);
        }
    }

    public function searcheorder() {
        $this->assign("list", "");
        if ($this->request->isPost()) {

//            [ info ] [ PARAM ] array (
//  'user_nickname-operate' => '=',
//  'user_nickname' => '01',
//  'order_no-operate' => '=',
//  'order_no' => '',
//  'createtime-operate' => 'RANGE',
//  'createtime' => '',
//  'paymenttime-operate' => 'RANGE',
//  'paymenttime' => '',
//  	<option value="1">3000</option>
//							<option value="2">5000</option>
//							<option value="3">15000</option>
//							<option value="4">30000</option>
//)

            $a = [0, 3000, 5000, 15000, 30000];
            $where = "";
            if (input("state") == 0) {
                $this->error("请选择价格");
            } else {
                $where .= " and pay.price=" . $a[input('state')];
            }

            if (null !== input("user_nickname")) {
                $uinfo = Db::name("user")->where(["nickname" => input("user_nickname")])->find();
                if ($uinfo) {
                    $ids = $this->getAllNextId($uinfo["id"]);
                } else {
                    $this->error("未找到该用户信息");
                }
            }
            if ($ids) {
                $where .= " and  user.id in (" . implode(',', $ids) . ")";
            }
            if (input("createtime") != "" and input("paymenttime") != "") {

                $where .= " and order.createtie>" . input("createtime") . " and order.createtie<" . input("paymenttime");
            }

            $sql = "SELECT `order`.`id`,`order`.`user_id`,`order`.`shop_id`,`order`.`order_no`,`order`.`address_id`,`order`.`coupon_id`,
`order`.`isaddress`,`order`.`freight_type`,`order`.`express_name`,`order`.`express_no`,`order`.`state`,`order`.`remarks`,`order`.`createtime`,
`order`.`paymenttime`,`order`.`delivertime`,`order`.`taketime`,`order`.`dealtime`,`order`.`updatetime`,`order`.`deletetime`,`order`.`status`,
`order`.`leader_id`,`order`.`goods_id`,`order`.`pay_status`,`order`.`wuliu`,user.id AS user__id,user.group_id AS user__group_id,user.username
 AS user__username,user.nickname AS user__nickname,user.password AS user__password,user.salt AS user__salt,user.email AS user__email,user.mobile AS user__mobile,
 user.avatar AS user__avatar,user.level AS user__level,user.gender AS user__gender,user.birthday AS user__birthday,user.bio AS user__bio,user.money AS user__money,
 user.score AS user__score,user.successions AS user__successions,user.maxsuccessions AS user__maxsuccessions,user.prevtime AS user__prevtime,user.logintime AS user__logintime,
 user.loginip AS user__loginip,user.loginfailure AS user__loginfailure,user.joinip AS user__joinip,user.jointime AS user__jointime,user.createtime AS user__createtime,
 user.updatetime AS user__updatetime,user.token AS user__token,user.status AS user__status,user.verification AS user__verification,user.recommend AS user__recommend,
 user.pids AS user__pids,user.integral AS user__integral,user.total_integral AS user__total_integral,user.agentcode AS user__agentcode,user.pid AS user__pid,
 user.is_blacklist AS user__is_blacklist,user.usdt AS user__usdt,user.prate AS user__prate,user.cardnum AS user__cardnum,user.truename AS user__truename,user.card0 AS user__card0,
 user.card1 AS user__card1,user.flag AS user__flag,user.balance AS user__balance,user.buycoupon AS user__buycoupon,user.city AS user__city,user.country AS user__country,user.province 
 AS user__province,user.grade AS user__grade,user.agent AS user__agent,user.openid AS user__openid,user.nowithdraw AS user__nowithdraw,user.noget AS user__noget,user.nodyc AS user__nodyc,
 shop.id AS shop__id,shop.user_id AS shop__user_id,shop.shopname AS shop__shopname,shop.keywords AS shop__keywords,shop.description AS shop__description,shop.service_ids AS 
 shop__service_ids,shop.avatar AS shop__avatar,shop.state AS shop__state,shop.level AS shop__level,shop.islive AS shop__islive,shop.isself AS shop__isself,shop.bio AS shop__bio,shop.city 
 AS shop__city,shop.return AS shop__return,shop.like AS shop__like,shop.score_describe AS shop__score_describe,shop.score_service AS shop__score_service,shop.score_deliver
 AS shop__score_deliver,shop.score_logistics AS shop__score_logistics,shop.weigh AS shop__weigh,shop.verify AS shop__verify,shop.createtime AS shop__createtime,shop.updatetime 
 AS shop__updatetime,shop.deletetime AS shop__deletetime,shop.status AS shop__status,pay.id AS pay__id,pay.pay_no AS pay__pay_no,pay.trade_no AS pay__trade_no,pay.user_id AS pay__user_id
 ,pay.shop_id AS pay__shop_id,pay.order_id AS pay__order_id,pay.order_no AS pay__order_no,pay.pay_type AS pay__pay_type,pay.pay_state AS pay__pay_state,pay.number AS pay__number,pay.price 
 AS pay__price,pay.order_price AS pay__order_price,pay.freight_price AS pay__freight_price,pay.coupon_price AS pay__coupon_price,pay.discount_price AS pay__discount_price,pay.refund_price AS
 pay__refund_price,pay.actual_payment AS pay__actual_payment,pay.total_amount AS pay__total_amount,pay.notice AS pay__notice,pay.createtime AS pay__createtime,pay.updatetime AS pay__updatetime,
 pay.deletetime AS pay__deletetime,pay.status AS pay__status,pay.usecoupon AS pay__usecoupon,pay.leader_id AS pay__leader_id,pay.loop_times AS pay__loop_times,pay.loop_status AS pay__loop_status,
 pay.win AS pay__win FROM `xsh_wanlshop_order` `order` LEFT JOIN `xsh_user` `user` ON `order`.`user_id`=`user`.`id` LEFT JOIN `xsh_wanlshop_shop` `shop` ON `order`.`shop_id`=`shop`.`id` 
 LEFT JOIN `xsh_wanlshop_pay` `pay` ON `order`.`id`=`pay`.`order_id` WHERE 1=1  " . $where . " AND `order`.`deletetime` IS NULL and order.state>=2 and order.state<=4  ORDER BY `order`.`id` DESC LIMIT 0,15";
            $list = Db::query($sql);
            $html = "";
            foreach ($list as $key => $value) {
                $ginfo = Db::name("wanlshop_goods")->where(["id" => $value["goods_id"]])->find();
                $html .= "<div class=\"wanl_order_list col-sm-12\">
        <table class=\"table table-bordered table-hover \">
            <thead>
                <tr>
                    <th colspan=\"7\">
                        <div class=\"th-inner\">
                            <label style=\"margin-left: 2px;\" for=\"order_60\">订单号：" . $value["order_no"] . "</label>
                            <label style=\"margin-left:60px;\" for=\"order_60\">创建时间：" . date("Y-m-d H:i:s", $value["createtime"]) . "</label>
                        </div>
                    </th>
                </tr>			   
            </thead>
            <tbody>
                
                <tr>
                    <td class=\"conceal\">
                        <div class=\"item\">
                            <div class=\"order_img\">
                                <a href=\"javascript:\"><img class=\"img-md img-center\" src=\"https://miaoxiang.oss-cn-beijing.aliyuncs.com/" . $ginfo["image"] . "\" alt=\"报单产品\"></a>
                            </div>
                            <div class=\"order_info\">
                                <p>" . $ginfo["title"] . "</p>
                            </div>
                        </div>
                    </td>
                    <td class=\"conceal fix-108\">
                        <p> ￥" . $ginfo["price"] . " </p>
                    </td>
                    <td class=\"conceal fix-108\">
                        <p>x1</p>	
                       
                    </td>
                    
                    <td class=\"fix-108\">
                        <p style=\"margin-bottom:5px;\">
                          " . $value["user__nickname"] . "
                        </p>
                    </td>
                    <td class=\"fix-108\">
                        <p><strong>￥" . $ginfo["price"] . "</strong> </p>
                        <p style=\"color:#6c6c6c;font-family:verdana;margin-bottom:1px;\"> (含运费：￥0.00) </p>
                    </td>
                    <td class=\"fix-108 operation\">
                        
                      
                        
                    </td>
                    <td class=\"fix-108\">
                  
                    </td>
                    
                </tr>
                
            </tbody>
        </table>
    </div>";
            }

            $this->assign("list", $html);
        }


        return $this->view->fetch();
    }

    public function getAllNextId($id, $data = []) {

        $pids = Db::name('user')->where('pid', $id)->column('id');
        if (count($pids) > 0) {
            foreach ($pids as $v) {
                $data[] = $v;
                $data = $this->getAllNextId($v, $data); //注意写$data 返回给上级
            }
        }

        if (count($data) > 0) {
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 查看
     */
    public function index() {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user', 'pay', 'ordergoods'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user', 'pay', 'ordergoods'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->getRelation('user')->visible(['id', 'username', 'nickname', 'avatar']);
                $row->getRelation('pay')->visible(['pay_no', 'price', 'order_price', 'freight_price', 'discount_price', 'actual_payment']);
                $addressInfo = \think\Db::name("wanlshop_order_address")->where(["order_id" => $row["id"]])->find();
                if ($addressInfo) {
                    $row["AddressInfo"] = $addressInfo;
                }
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($id = null) {
        $row = $this->model->get($id);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        // 判断权限
        if ($row['shop_id'] != $this->shop->id) {
            $this->error(__('You have no permission'));
        }
        $row['address'] = model('app\index\model\wanlshop\OrderAddress')
                ->where(['order_id' => $id, 'shop_id' => $this->shop->id])
                ->order('isaddress desc')
                ->field('id,name,mobile,address,address_name')
                ->find();
        // 查询快递状态
        switch ($row['state']) {
            case 1:
                $express = [
                    'context' => '付款后，即可将宝贝发出',
                    'status' => '尚未付款',
                    'time' => date('Y-m-s h:i:s', $row['createtime'])
                ];
                break;
            case 2:
                $express = [
                    'context' => '商家正在处理订单',
                    'status' => '已付款',
                    'time' => date('Y-m-s h:i:s', $row['paymenttime'])
                ];
                break;
            default: // 获取物流
                $eData = model('app\api\model\wanlshop\KuaidiSub')
                        ->where(['express_no' => $row['express_no']])
                        ->find();
                $ybData = json_decode($eData['data'], true);
                if ($ybData) {
                    $express = $ybData[0];
                } else {
                    $express = [
                        'status' => '已发货',
                        'context' => '包裹正在等待快递小哥揽收~',
                        'time' => date('Y-m-s h:i:s', $row['delivertime'])
                    ];
                }
        }
        $this->view->assign("kuaidi", $express);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 快递查询
     */
    public function relative($id = null) {
        $row = $this->model->get($id);

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        // 判断权限
        if ($row['shop_id'] != $this->shop->id) {
            $this->error(__('You have no permission'));
        }
        $data = model('app\index\model\wanlshop\KuaidiSub')
                ->where(['express_no' => $row['express_no']])
                ->find();
        $config = getKuaidi($data["express_no"]);
        $data = \GuzzleHttp\json_decode($config);
        // dump($result->status);

        $data = $data->result->list; // json_decode(, true);
        //dump($data);
        $list = [];
        $week = array(
            "0" => "星期日",
            "1" => "星期一",
            "2" => "星期二",
            "3" => "星期三",
            "4" => "星期四",
            "5" => "星期五",
            "6" => "星期六"
        );
        if ($data) {
            foreach ($data as $vo) {
                $list[] = [
                    'time' => strtotime($vo->time),
                    'status' => $vo->status,
                    'context' => $vo->status,
                    'week' => $week[date('w', strtotime($vo->time))]
                ];
            }
        }
        $this->view->assign("week", $week);
        $this->view->assign("list", $list);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 打印发货单
     */
    public function invoice($ids = null) {
        $row = $this->model->all($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        foreach ($row as $data) {
            // 判断权限
            if ($data['shop_id'] != $this->shop->id) {
                $this->error(__('You have no permission'));
            }
            $data['address'] = model('app\index\model\wanlshop\OrderAddress')
                    ->where(['order_id' => $data['id'], 'shop_id' => $this->shop->id])
                    ->order('isaddress desc')
                    ->field('id,name,mobile,address,address_name')
                    ->find();
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 发货 &批量发货
     */
    public function delivery($ids = null) {
        $data = [];
        $lists = [];
        $row = $this->model->all($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        foreach ($row as $vo) {
            if ($vo['shop_id'] != $this->shop->id) {
                $this->error(__('You have no permission'));
            }
            $vo['address'] = model('app\index\model\wanlshop\OrderAddress')
                    ->where(['order_id' => $vo['id'], 'shop_id' => $this->shop->id])
                    ->order('isaddress desc')
                    ->field('id,name,mobile,address,address_name')
                    ->find();
            if ($vo['state'] == 2) {
                $lists[] = $vo;
            } else {
                $data[] = $vo;
            }
        }
        if ($this->request->isAjax()) {
            $request = $this->request->post();
            if (!array_key_exists("order", $request['row'])) {
                $this->success(__('没有发现可以发货订单~'));
            }
// 	if(!$this->wanlchat->isWsStart()){
// 	$this->error('平台未启动IM即时通讯服务，暂时不可以发货');
// 	}
//            $possn= $request["possn"];
//       
//            foreach ($possn as $key=>$value){
//                $sn= explode(",", $value);
//                $a=0;
//                foreach ($sn as $k=>$v){
//                    if($a!=0){
//                        $snStr.=$v.",";
//                    }
//                      $a++;
//                }
//            }
//            if(strlen($snStr)>0){
//                $snStr= substr($snStr, 0, strlen($snStr)-1);
//                $poslist=Db::name("kaxin_pos")->whereIn("possn", $snStr)->select();
//                dump($poslist);
//            }

            $config = get_addon_config('wanlshop');
            $ehund = new Ehund($config['kuaidi']['secretKey'], $config['ini']['appurl'] . $config['kuaidi']['callbackUrl']);
            $order = [];
            $user_sn = [];
            foreach ($request['row']['order']['id'] as $key => $id) {
                $express_no = $request['row']['order']['express_no'][$key];
                $postsn = $request["possn"][$key];
                $sn = explode(",", $postsn);
                $a = 0;
                $snStr = "";
                $orderInfo = \app\api\model\wanlshop\Order::get($id);
                foreach ($sn as $k => $v) {
                    if ($a != 0) {
                        $snStr .= $v . ",";
                        $user_sn[] = [
                            "uid" => $orderInfo["user_id"],
                            "snid" => strval($v),
                            "flag" => 0
                        ];
                    }
                    $a++;
                }
                
            
                if (strlen($snStr) > 0) {

                    $snStr = substr($snStr, 0, strlen($snStr) - 1);
                    $poslist = Db::name("kaxin_pos")->whereIn("possn", $snStr)->update(["flag" => 1]);
                }

              

                $express_name = $request['row']['express_name'];
                $order[] = [
                    'id' => $id,
                    'express_name' => $express_name,
                    'express_no' => $express_no,
                    'delivertime' => time(),
                    'state' => 3
                ];
                // 订阅快递查询
//                if ($config['kuaidi']['secretKey']) {
//                    $returncode = $ehund->subScribe($express_name, $express_no);
//                    if ($returncode['returnCode'] != 200) {
//                        $this->error('快递订阅接口异常-'.$returncode['message']);
//                    }
//                    $express[] = [
//                        'sign' => $ehund->sign($express_no),
//                        'express_no' => $express_no,
//                        'returncode' => $returncode['returnCode'],
//                        'message' => $returncode['message']
//                    ];
//                }
                // 推送消息
                //$this->pushOrder($id,'已发货');
            }
        
            Db::name("kaxin_user_sn")->insertAll($user_sn);
            
            $this->model->saveAll($order);
            // 写入快递订阅列表
            //   if ($config['kuaidi']['secretKey']) {
            //  model('app\index\model\wanlshop\KuaidiSub')->saveAll($express);
            // }
            $this->success();
        }
        $this->view->assign("lists", $lists); //可以发货
        $this->view->assign("data", $data);
        return $this->view->fetch();
    }

    /**
     * 评论管理
     */
    public function comment() {
        return $this->view->fetch('wanlshop/comment/index');
    }

    public function download() {
        return $this->fetch();
    }

    /**
     * 订单推送消息（方法内使用）
     * 
     * @param string order_id 订单ID
     * @param string state 状态
     */
    private function pushOrder($order_id = 0, $state = '已发货') {
        $order = $this->model->get($order_id);
        $orderGoods = model('app\index\model\wanlshop\OrderGoods')
                ->where(['order_id' => $order_id])
                ->select();
        $msgData = [];
        foreach ($orderGoods as $goods) {
            $msg = [
                'user_id' => $order['user_id'], // 推送目标用户
                'shop_id' => $this->shop->id,
                'title' => '您的订单' . $state, // 推送标题
                'image' => $goods['image'], // 推送图片
                'content' => '您购买的商品 ' . (mb_strlen($goods['title'], 'utf8') >= 25 ? mb_substr($goods['title'], 0, 25, 'utf-8') . '...' : $goods['title']) . ' ' . $state,
                'type' => 'order', // 推送类型
                'modules' => 'order', // 模块类型
                'modules_id' => $order_id, // 模块ID
                'come' => '订单' . $order['order_no'] // 来自
            ];
            $msgData[] = $msg;
            $this->wanlchat->send($order['user_id'], $msg);
        }
        $notice = model('app\index\model\wanlshop\Notice')->saveAll($msgData);
    }

}

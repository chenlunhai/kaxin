<?php

namespace app\api\controller\wanlshop;

use app\common\controller\Api;
use think\Db;

/**
 * WanlShop订单接口
 */
class Order extends Api {

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 获取订单列表
     *
     * @ApiSummary  (WanlShop 订单接口获取订单列表)
     * @ApiMethod   (GET)
     * 2020年5月12日23:25:40
     *
     * @param string $state 状态
     */
    public function getOrderList() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $state = $this->request->request('state');
        if ($state && $state != 0) {
            $where['state'] = $state;
        }
        $where['status'] = 'normal';
        $where['user_id'] = $this->auth->id;
        // 列表	
        $list = model('app\api\model\wanlshop\Order')
                ->where($where)
                ->field('id,shop_id,state,express_no')
                ->order('updatetime desc')
                ->paginate()
                ->each(function ($order, $key) {
            $goods = model('app\api\model\wanlshop\OrderGoods')
                    ->where(['order_id' => $order->id])
                    ->field('id,title,image,difference,price,market_price,number,refund_status')
                    ->select();
            $order['goods'] = $goods;
            $order['pay'] = $order->pay ? $order->pay->visible(['number', 'price', 'order_price', 'freight_price', 'discount_price', 'actual_payment']) : [];
            $order['shop'] = $order->shop ? $order->shop->visible(['shopname']) : [];
            return $order;
        });
        $list ? ($this->success('ok', $list)) : ($this->error(__('网络繁忙')));
    }

    /**
     * 查询用户店铺订单记录 
     *
     * @ApiSummary  (查询用户店铺订单记录 1.0.2升级)
     * @ApiMethod   (POST)
     *
     * @param string $shop_id 店铺ID
     */
    public function getOrderListToShop() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $shop_id = $this->request->post('shop_id');
            $shop_id ? '' : ($this->error(__('Invalid parameters')));
            $list = model('app\api\model\wanlshop\Order')
                    ->where(['shop_id' => $shop_id, 'user_id' => $this->auth->id, 'status' => 'normal'])
                    ->field('id,shop_id,order_no,state')
                    ->order('updatetime desc')
                    ->select();
            // 订单状态:1=待支付,2=待发货,3=待收货,4=待评论,5=售后订单(已弃用),6=已完成,7=已取消
            foreach ($list as $row) {
                $row['goods'] = model('app\api\model\wanlshop\OrderGoods')
                        ->where(['order_id' => $row->id])
                        ->field('id,title,image,difference,price,market_price,number,refund_status')
                        ->select();
            }
            $this->success(__('发送成功'), $list);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取订单详情
     *
     * @ApiSummary  (WanlShop 订单接口获取订单详情)
     * @ApiMethod   (GET)
     * 
     * @param string $id 订单ID
     */
    public function getOrderInfo() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $id = $this->request->request('id');
        $id ? $id : ($this->error(__('非法请求')));
        $order = model('app\api\model\wanlshop\Order')
                ->where(['id' => $id, 'user_id' => $this->auth->id])
                ->field('id,shop_id,order_no,isaddress,express_no,express_name,freight_type,state,createtime,paymenttime,delivertime,taketime,dealtime')
                ->find();
        $order ? $order : ($this->error(__('网络异常')));
        // 输出配置
        $config = get_addon_config('wanlshop');
        $order['config'] = $config['order'];
        switch ($order['state']) {
            case 1:
                $express = [
                    'context' => '付款后，即可将宝贝发出',
                    'status' => '尚未付款',
                    'time' => date('Y-m-s h:i:s', $order['createtime'])
                ];
                break;
            case 2:
                $express = [
                    'context' => '商家正在处理订单',
                    'status' => '已付款',
                    'time' => date('Y-m-s h:i:s', $order['paymenttime'])
                ];
                break;
            default: // 获取物流
                $eData = model('app\api\model\wanlshop\KuaidiSub')
                        ->where(['express_no' => $order['express_no']])
                        ->find();
                $ybData = json_decode($eData['data'], true);
                if ($ybData) {
                    $express = $ybData[0];
                } else {
                    $express = [
                        'status' => '已发货',
                        'context' => '包裹正在等待快递小哥揽收~',
                        'time' => date('Y-m-s h:i:s', $order['delivertime'])
                    ];
                }
        }
        // 获取物流
        $order['logistics'] = $express;
        // 获取地址
        $order['address'] = model('app\api\model\wanlshop\OrderAddress')
                ->where(['order_id' => $id, 'user_id' => $this->auth->id])
                ->order('isaddress desc')
                ->field('id,name,mobile,address,address_name')
                ->find();
        // 获取店铺
        $order['shop'] = $order->shop ? $order->shop->visible(['shopname']) : [];
        // 获取订单商品
        $order['goods'] = model('app\api\model\wanlshop\OrderGoods')
                ->where(['order_id' => $id])
                ->field('id,goods_id,title,image,difference,price,market_price,freight_price,number,refund_id,refund_status')
                ->select();
        // 获取支付
        $order['pay'] = $order->pay ? $order->pay->visible(['id', 'pay_no', 'number', 'price', 'order_price', 'freight_price', 'discount_price', 'actual_payment']) : [];
        $this->success('ok', $order);
    }

    /**
     * 确认收货
     *
     * @ApiSummary  (WanlShop 订单接口确认收货)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID                       ------ 后续版本优化 Db::startTrans();
     */
    public function confirmOrder() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $id ? $id : ($this->error(__('非法请求')));
            // 判断权限
            $order = model('app\api\model\wanlshop\Order')
                    ->where(['id' => $id, 'state' => 3, 'user_id' => $this->auth->id])
                    ->find();
            if (!$order) {
                $this->error(__('订单异常'));
            }
            // 平台转款给商家
            controller('addons\wanlshop\library\WanlPay\WanlPay')->money(+$order['pay']['price'], $order['shop']['user_id'], '买家确认收货', 'pay', $order['order_no']);
            // 查询是否有退款
            $refund = model('app\api\model\wanlshop\Refund')
                    ->where(['order_id' => $id, 'state' => 4])
                    ->select();
            // 退款存在
            if ($refund) {
                foreach ($refund as $value) {
                    controller('addons\wanlshop\library\WanlPay\WanlPay')->money(-$value['price'], $order['shop']['user_id'], '该订单存在的退款', 'pay', $order['order_no']);
                }
            }
//            $rate = \app\api\controller\Tools::getConfig1("city_agent_rate");
//            $adinfo = \app\index\model\wanlshop\Address::get($order["address_id"]);
//            $rate_pro = \app\api\controller\Tools::getConfig1("pro_agent_rate");
//            $pro = Db::name("area")->where(["name" => $adinfo["province"]])->find();
//            if ($pro) {
//                $uinfo = \app\common\model\User::get(["agentcode" => $pro["id"]]);
//                if ($uinfo) {
//                    $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
//                    $forLog->money($order['pay']['price'] * $rate_pro, $uinfo["id"], "省代奖励", 'sys', $id, 4);
//                }
//            }
//            $city = Db::name("area")->where(["name" => $adinfo["city"]])->find();
//            if ($city) {
//                $uinfo = \app\common\model\User::get(["agentcode" => $city["id"]]);
//                if ($uinfo) {
//                    $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
//                    $forLog->money($order['pay']['price'] * $rate, $uinfo["id"], "市代奖励", 'sys', $id, 4);
//                }
//            }
//            $rate_contry = \app\api\controller\Tools::getConfig1("country_agent_rate");
//            $country = Db::name("area")->where(["name" => $adinfo["district"]])->find();
//            if ($city) {
//                $uinfo = \app\common\model\User::get(["agentcode" => $country["id"]]);
//                if ($uinfo) {
//                    $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
//                    $forLog->money($order['pay']['price'] * $rate_contry, $uinfo["id"], "区代奖励", 'sys', $id, 4);
//                }
//            }
            // 更新退款
            $order->save(['state' => 6, 'taketime' => time()], ['id' => $id]);
            $this->success('ok', $order ? true : false);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 取消订单
     *
     * @ApiSummary  (WanlShop 订单接口取消订单)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function cancelOrder() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $id ? $id : ($this->error(__('非法请求')));
            // 判断权限
            $this->getOrderState($id) != 1 ? ($this->error(__('订单异常'))) : '';
            $row = model('app\api\model\wanlshop\Order')->get(['id' => $id, 'user_id' => $this->auth->id]);
            $result = $row->allowField(true)->save(['state' => 9]);
            // 还原优惠券 1.0.2升级
            if ($row['coupon_id'] != 0) {
                model('app\api\model\wanlshop\CouponReceive')->where(['id' => $row['coupon_id'], 'user_id' => $this->auth->id])->update(['state' => 1]);
            }
            // 释放库存 1.0.3升级
            foreach (model('app\api\model\wanlshop\OrderGoods')->all(['order_id' => $row['id']]) as $vo) {
                // 查询商品是否需要释放库存
                if (model('app\api\model\wanlshop\Goods')->get($vo['goods_id'])['stock'] == 'porder') {
                    model('app\api\model\wanlshop\GoodsSku')->where('id', $vo['goods_sku_id'])->setInc('stock', $vo['number']);
                }
            }
            $this->success('ok', $result ? true : false);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 删除订单
     *
     * @ApiSummary  (WanlShop 订单接口删除订单)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function delOrder() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $id ? $id : ($this->error(__('非法请求')));
            // 判断权限
            $state = $this->getOrderState($id);
            $state == 6 || $state == 7 ? '' : ($this->error(__('非法请求')));
            $order = model('app\api\model\wanlshop\Order')
                    ->save(['status' => 'hidden'], ['id' => $id]);
            $this->success('ok', $order ? true : false);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 修改地址
     *
     * @ApiSummary  (WanlShop 订单接口修改地址)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function editOrderAddress() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $order_id = $this->request->post('id');
            $address_id = $this->request->post('address_id');
            $order_id || $address_id ? $order_id : ($this->error(__('非法请求')));
            // 判断权限
            $this->getOrderState($order_id) > 3 ? ($this->error(__('订单异常'))) : '';
            // 订单
            $order = model('app\api\model\wanlshop\Order')
                    ->where(['id' => $order_id, 'user_id' => $this->auth->id])
                    ->find();

            //判断是否修改过
            if ($order['isaddress'] == 1) {
                $this->error(__('已经修改过一次了'));
            } else {
                // 获取地址
                $address = model('app\api\model\wanlshop\Address')
                        ->where(['id' => $address_id, 'user_id' => $this->auth->id])
                        ->find();
                // 修改地址
                $data = model('app\api\model\wanlshop\OrderAddress')->save([
                    'user_id' => $this->auth->id,
                    'shop_id' => $order->shop_id,
                    'order_id' => $order_id,
                    'isaddress' => 1,
                    'name' => $address['name'],
                    'mobile' => $address['mobile'],
                    'address' => $address['province'] . '/' . $address['city'] . '/' . $address['district'] . '/' . $address['address'],
                    'address_name' => $address['address_name'],
                    'location' => $address['location']
                ]);
                // 修改状态
                model('app\api\model\wanlshop\Order')->where(['id' => $order_id, 'user_id' => $this->auth->id])->update(['isaddress' => 1]);
                $this->success('ok', $data);
            }
        }
        $this->error(__('非法请求'));
    }

    /**
     * 评论订单
     *
     * @ApiSummary  (WanlShop 订单接口评论订单)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function commentOrder() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post ? $post : ($this->error(__('数据异常')));
            $user_id = $this->auth->id;
            // 判断权限
            $this->getOrderState($post['order_id']) != 4 ? ($this->error(__('已经评论过或订单异常'))) : '';
            // 生成列表
            $commentData = [];
            foreach ($post['goodsList'] as $value) {
                $commentData[] = [
                    'user_id' => $user_id,
                    'shop_id' => $post['shop']['id'],
                    'order_id' => $post['order_id'],
                    'goods_id' => $value['goods_id'],
                    'order_goods_id' => $value['id'],
                    'state' => $value['state'],
                    'content' => $value['comment'],
                    'suk' => $value['difference'],
                    'images' => $value['imgList'],
                    'score' => round((($post['shop']['describe'] + $post['shop']['service'] + $post['shop']['deliver'] + $post['shop']['logistics']) / 4), 1),
                    'score_describe' => $post['shop']['describe'],
                    'score_service' => $post['shop']['service'],
                    'score_deliver' => $post['shop']['deliver'],
                    'score_logistics' => $post['shop']['logistics'],
                    'switch' => 0
                ];
                //评论暂不考虑并发，为列表提供好评付款率，减少并发只能写进商品中
                model('app\api\model\wanlshop\Goods')->where(['id' => $value['goods_id']])->setInc('comment');
                if ($value['state'] == 0) {
                    model('app\api\model\wanlshop\Goods')->where(['id' => $value['goods_id']])->setInc('praise');
                } else if ($value['state'] == 1) {
                    model('app\api\model\wanlshop\Goods')->where(['id' => $value['goods_id']])->setInc('moderate');
                } else if ($value['state'] == 2) {
                    model('app\api\model\wanlshop\Goods')->where(['id' => $value['goods_id']])->setInc('negative');
                }
            }
            if (model('app\api\model\wanlshop\GoodsComment')->saveAll($commentData)) {
                $order = model('app\api\model\wanlshop\Order')
                        ->where(['id' => $post['order_id'], 'user_id' => $user_id])
                        ->update(['state' => 6]);
            }
            //更新店铺评分
            $score = model('app\api\model\wanlshop\GoodsComment')
                    ->where(['user_id' => $user_id])
                    ->select();
            // 从数据集中取出
            $describe = array_column($score, 'score_describe');
            $service = array_column($score, 'score_service');
            $deliver = array_column($score, 'score_deliver');
            $logistics = array_column($score, 'score_logistics');
            // 更新店铺评分
            model('app\api\model\wanlshop\Shop')
                    ->where(['id' => $post['shop']['id']])
                    ->update([
                        'score_describe' => bcdiv(array_sum($describe), count($describe), 1),
                        'score_service' => bcdiv(array_sum($service), count($service), 1),
                        'score_deliver' => bcdiv(array_sum($deliver), count($deliver), 1),
                        'score_logistics' => bcdiv(array_sum($logistics), count($logistics), 1)
            ]);
            $this->success('ok', []);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取订单物流状态
     *
     * @ApiSummary  (WanlShop 订单接口获取订单物流状态)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function getLogistics() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $id ? $id : ($this->error(__('非法请求')));
            //获取订单
            $order = model('app\api\model\wanlshop\Order')
                    ->where(['id' => $id, 'user_id' => $this->auth->id])
                    ->field('id,shop_id,express_name,express_no,order_no,createtime,paymenttime,delivertime')
                    ->find();
            // 获取快递
            switch ($order['state']) {
                case 1:
                    $express[] = [
                        'context' => '付款后，即可将宝贝发出',
                        'status' => '尚未付款',
                        'time' => date('Y-m-s h:i:s', $order['createtime'])
                    ];
                    break;
                case 2:
                    $express[] = [
                        'context' => '商家接受到您的订单，准备出库',
                        'status' => '已下单',
                        'time' => date('Y-m-s h:i:s', $order['paymenttime'])
                    ];
                    break;
                default: // 获取物流
                    $express = model('app\api\model\wanlshop\KuaidiSub')
                            ->where(['express_no' => $order['express_no']])
                            ->find();
                    $config = getKuaidi($express["express_no"]);
                    $data = \GuzzleHttp\json_decode($config);
                    // dump($result->status);

                    $data = $data->result->list; // json_decode(, true);

                    if ($express) {
                        $express = $data; //json_decode($express['data'], true);
                    } else {
                        $express[] = [
                            'context' => '打包完成，正在等待快递小哥揽收~',
                            'status' => '仓库处理中',
                            'time' => date('Y-m-s h:i:s', $order['delivertime'])
                        ];
                    }
            }
            $order['kuaidi'] = $order->kuaidi ? $order->kuaidi->visible(['name', 'logo', 'tel']) : [];
            $order['express'] = $express;
            $this->success('ok', $order);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 确认订单
     *
     * @ApiSummary  (WanlShop 订单接口确认订单)
     * @ApiMethod   (POST)
     * 
     * @param string $data 商品数据
     */
    public function getOrderGoodsList() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $request = $this->request->post();
            // 订单数据
            $order = array();
            $map = array();
            // 地址
            $address = model('app\api\model\wanlshop\Address')
                    ->where(['user_id' => $this->auth->id, 'default' => 1])
                    ->field('id,name,mobile,province,city,district,address')
                    ->find();
            // 合计
            $statis = array(
                "allnum" => 0,
                "allsub" => 0
            );
            foreach ($request as $post) {
                // 商品详情
                $goods = model('app\api\model\wanlshop\Goods')
                        ->where('id', $post['goods_id'])
                        ->field('id,shop_id,shop_category_id,title,image,stock,freight_id,sales')
                        ->find();
                // 获取SKU
                $sku = model('app\api\model\wanlshop\GoodsSku')
                        ->where('id', $post['sku_id'])
                        ->field('id,difference,price,market_price,stock,weigh')
                        ->find();
                // 获取快递及价格
                $goods['freight'] = $this->freight($goods['freight_id'], $sku['weigh'], $post['number'], $address['city']);
                // 获取SKU
                $goods['sku'] = $sku;
                // 数量
                $goods['number'] = $post['number'];
                // 格式化
                if (empty($map[$goods['shop_id']])) {
                    $order[] = array(
                        "shop_id" => $goods['shop_id'],
                        "shop_name" => $goods->shop ? $goods->shop->visible(['shopname'])['shopname'] : [],
                        "products" => [$goods],
                        "coupon" => [],
                        "freight" => [$goods['freight']],
                        "number" => $goods['number'],
                        "sub_price" => bcmul($sku['price'], $goods['number'], 2)
                    );
                    $map[$goods['shop_id']] = $goods;
                } else {
                    // 追加1-*
                    foreach ($order as $key => $value) {
                        if ($value['shop_id'] == $goods['shop_id']) {
                            array_push($order[$key]['products'], $goods);
                            array_push($order[$key]['freight'], $goods['freight']);
                            $order[$key]['number'] += $post['number'];
                            $order[$key]['sub_price'] = bcadd($order[$key]['sub_price'], bcmul($sku['price'], $post['number'], 2), 2);
                            break;
                        }
                    }
                }
                // 所有店铺统计
                $statis['allnum'] += $goods['number'];
            }
            // 获取运费策略-店铺循环
            foreach ($order as $key => $value) {
                $config = model('app\api\model\wanlshop\ShopConfig')
                        ->where('shop_id', $value['shop_id'])
                        ->find();
                if ($config['freight'] == 0) {
                    // 运费叠加
                    $order[$key]['freight'] = [
                        'id' => $value['freight'][0]['id'],
                        'name' => '运费叠加',
                        'price' => array_sum(array_column($value['freight'], 'price'))
                    ];
                } else if ($config['freight'] == 1) {
                    // 以最低结算
                    array_multisort(array_column($value['freight'], 'price'), SORT_ASC, $value['freight']);
                    $order[$key]['freight'] = [
                        'id' => $value['freight'][0]['id'],
                        'name' => $value['freight'][0]['name'],
                        'price' => $value['freight'][0]['price']
                    ];
                } else if ($config['freight'] == 2) {
                    // 以最高结算
                    array_multisort(array_column($value['freight'], 'price'), SORT_DESC, $value['freight']);
                    $order[$key]['freight'] = [
                        'id' => $value['freight'][0]['id'],
                        'name' => $value['freight'][0]['name'],
                        'price' => $value['freight'][0]['price']
                    ];
                }
                $order[$key]['order_price'] = $order[$key]['sub_price'];
                // 2020年9月19日12:10:59 添加快递价格备份,用于还原运费
                $order[$key]['freight_price_bak'] = $order[$key]['freight']['price'];
                $order[$key]['sub_price'] += $order[$key]['freight']['price'];
                $statis['allsub'] += $order[$key]['sub_price'];
            }
            // 地址
            $datalist['addressData'] = $address;
            // 订单
            $datalist['orderData']['lists'] = $order;
            $datalist['orderData']['statis'] = $statis;
            $this->success('ok', $datalist);
        } else {
            $this->error(__('非法请求'));
        }
    }

    /**
     * 提交订单
     *
     * @ApiSummary  (WanlShop 订单接口提交订单)
     * @ApiMethod   (POST)
     * 
     * @param string $data 数组
     */
    public function addOrder() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $user_id = $this->auth->id; // 用户ID
            if (isset($params["wuliu"])) {
                $uinfo = Db::name("user")->where(["id" => $user_id])->find();
                if ($uinfo["nowithdraw"] == 'hidden') {
                    $this->error(__('该账号被禁止兑换'));
                }
            }
            //
            // 判断是否有地址 1.0.2升级
            if (array_key_exists('address_id', $params['order'])) {
                $address_id = $params['order']['address_id']; // 地址ID
            } else {
                $this->error(__('请点击上方添加收货地址'));
            }

            // 判断订单是否合法 1.0.4升级
            if (array_key_exists('lists', $params['order'])) {
                $lists = $params['order']['lists'];
                if (!isset($lists) && count($lists) == 0) {
                    $this->error(__('订单繁忙ERR001：请返回商品详情重新提交订单'));
                }
            } else {
                $this->error(__('订单繁忙ERR002：请返回商品详情重新提交订单'));
            }
            // 查询地址
            $address = model('app\api\model\wanlshop\Address')
                    ->where(['id' => $address_id, 'user_id' => $user_id])
                    ->find();
            // 1.0.4升级
            if (!isset($address)) {
                $this->error(__('地址异常，没有找到该地址'));
            }

            // 遍历订单列表
            $goodsList = [];
            $payList = [];
            foreach ($lists as $item) {
                // 获取店铺ID
                $shop_id = $item['shop_id'];
                // 获取优惠券，先查询免在保存中出错
                $coupon = model('app\api\model\wanlshop\CouponReceive')
                        ->where(['id' => $item['coupon_id'], 'user_id' => $user_id, 'shop_id' => $shop_id])
                        ->find();
                // 查询店铺配置
                $config = model('app\api\model\wanlshop\ShopConfig')
                        ->where('shop_id', $shop_id)
                        ->find();
                //如果不存在，按照累计运费
                if (!$config) {
                    $config['freight'] = 0;
                }
                // 生成订单
                $order = new \app\api\model\wanlshop\Order;
                $order->freight_type = $config['freight'];
                $order->user_id = $user_id;
                $order->shop_id = $shop_id;
                $flag = false;
                foreach ($item['products'] as $data) {
                    $goodsData = model('app\api\model\wanlshop\Goods')->get($data['goods_id']);
                    if ($goodsData["category_id"] == 105) {
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    $order->order_no = $shop_id . $user_id . 'Q';
                } else {
                    $order->order_no = $shop_id . $user_id;
                }
                if (isset($item['remarks'])) {
                    $order->remarks = $item['remarks'];
                }
                // 2020年9月19日 05:30:10 新增优惠券功能
                if (isset($params["wuliu"])) {

                    if ($params["wuliu"] == 0) {
                        $order->express_name = "自提寄售";
                        $order->express_no = "9999999999999";
                    } elseif ($params["wuliu"] == 2) {
                        $order->express_name = "买家自提";
                        $order->express_no = "8888888888888";
                    }
                }
                // 要补充活动ID
                // ...
                // 生成子列 ！！要保留原始商品，要获取当前商品，不可直接写入！！ 
                if ($order->save()) {
                    $priceAll = 0; // 总价格
                    $numberAll = 0; // 总数量
                    $freightALL = [];
                    // 计算订单价格
                    $gid = 0;
                    foreach ($item['products'] as $data) {
                        $goodsData = model('app\api\model\wanlshop\Goods')->get($data['goods_id']);
                        // 获取sku
                        $sku = model('app\api\model\wanlshop\GoodsSku')->get($data['sku_id']);
                        // 检查库存
                        if ($sku['stock'] == 0) {
                            $this->error(__('无法购买，库存不足'));
                        }
                        // 库存计算方式:porder=下单减库存,payment=付款减库存
                        if ($goodsData['stock'] == 'porder') {
                            $sku->setDec('stock', $data['number']); // 1.0.3升级
                        }
                        // 生成运费
                        $freight = $this->freight($goodsData['freight_id'], $sku['weigh'], $data['number'], $address['city']);
                        $goodsList[] = [
                            'order_id' => $order->id, // 获取自增ID
                            'goods_id' => $goodsData['id'],
                            'shop_id' => $shop_id,
                            'title' => $goodsData['title'],
                            'image' => $goodsData['image'],
                            'goods_sku_sn' => $sku['sn'],
                            'goods_sku_id' => $sku['id'],
                            'difference' => join(',', $sku['difference']),
                            'price' => $sku['price'],
                            'market_price' => $sku['market_price'],
                            'freight_price' => $freight['price'], //快递价格
                            'number' => $data['number']
                        ];
                        $freightALL[] = $freight;
                        $priceAll = bcadd($priceAll, bcmul($sku['price'], $data['number'], 2), 2); // 计算价格
                        $numberAll += $data['number']; // 计算数量
                        $order->goods_id = $gid = $goodsData['id'];
                        $order->save();
                    }
                    // 计算运费叠加方案
                    if ($config['freight'] == 0) {
                        // 运费叠加
                        $freight = [
                            'id' => $freightALL[0]['id'],
                            'name' => '合并运费',
                            'price' => array_sum(array_column($freightALL, 'price'))
                        ];
                    } else if ($config['freight'] == 1) { // 以最低结算
                        array_multisort(array_column($freightALL, 'price'), SORT_ASC, $freightALL);
                        $freight = [
                            'id' => $freightALL[0]['id'],
                            'name' => $freightALL[0]['name'],
                            'price' => $freightALL[0]['price']
                        ];
                    } else if ($config['freight'] == 2) { // 以最高结算
                        array_multisort(array_column($freightALL, 'price'), SORT_DESC, $freightALL);
                        $freight = [
                            'id' => $freightALL[0]['id'],
                            'name' => $freightALL[0]['name'],
                            'price' => $freightALL[0]['price']
                        ];
                    }
                    // 计算总价、优惠价格
                    $freight_price = $freight['price'];  //快递金额
                    $price = bcadd($priceAll, $freight_price, 2); // 总价格
                    $coupon_price = 0; //优惠券金额
                    $discount_price = 0; // 优惠金额，因为后续版本涉及到活动减免，所以优惠金额要单独拎出来
                    // 如果优惠券存在
                    if ($coupon) {
                        // 判断是否可用
                        if ($priceAll >= $coupon['limit']) {
                            if ($coupon['type'] == 'reduction' || ($coupon['type'] == 'vip' && $coupon['usertype'] == 'reduction')) {
                                $coupon_price = $coupon['price'];
                                //总金额 =（订单金额 - 优惠券金额）+ 运费
                                $price = bcadd(bcsub($priceAll, $coupon_price, 2), $freight['price'], 2);
                                // 减满的是金额直接赋值优惠金额即可
                                $discount_price = $coupon_price;
                            }
                            if ($coupon['type'] == 'discount' || ($coupon['type'] == 'vip' && $coupon['usertype'] == 'discount')) {
                                // 排除异常折扣，还原百分之
                                $discount = $coupon['discount'] > 10 ? bcdiv($coupon['discount'], 100, 2) : bcdiv($coupon['discount'], 10, 2);
                                // 优惠金额 = 订单金额 - 订单金额 * 折扣
                                $coupon_price = bcsub($priceAll, bcmul($priceAll, $discount, 2), 2);
                                $price = bcadd(bcsub($priceAll, $coupon_price, 2), $freight['price'], 2);
                                $discount_price = $coupon_price;
                            }
                            if ($coupon['type'] == 'shipping') {
                                $coupon_price = $freight_price;
                                $price = $priceAll;
                                $discount_price = $coupon_price;
                                $freight_price = 0;
                            }
                            // 安全检查，系统要求至少支付一分钱
                            if ($price <= 0) {
                                $price = 0.01;
                            }
                            // 更新已使用数量
                            model('app\api\model\wanlshop\Coupon')
                                    ->where(['id' => $coupon['coupon_id']])
                                    ->setInc('usenum');
                            // 修改该优惠券状态 已用
                            $coupon->allowField(true)->save(['state' => 2]);
                        } else {
                            model('app\api\model\wanlshop\Order')->destroy($order->id);
                            $this->error('订单金额' . $priceAll . '元，不满' . $coupon['limit'] . '元');
                        }
                    }
                    // 生成支付
                    $payList[] = [
                        'user_id' => $user_id,
                        'shop_id' => $shop_id,
                        'order_id' => $order->id,
                        'order_no' => $order->order_no,
                        'pay_no' => $order->order_no,
                        'order_price' => $priceAll,
                        'freight_price' => $freight_price,
                        'coupon_price' => $coupon_price,
                        'discount_price' => $discount_price,
                        'price' => $price,
                        'number' => $numberAll,
                            // 'goods_id' => $gid,
                    ];
                    // 生成地址
                    $addressList[] = [
                        'user_id' => $user_id,
                        'shop_id' => $shop_id,
                        'order_id' => $order->id,
                        'name' => $address['name'],
                        'mobile' => $address['mobile'],
                        'address' => $address['province'] . '/' . $address['city'] . '/' . $address['district'] . '/' . $address['address'],
                        'address_name' => $address['address_name'],
                        'location' => $address['location']
                    ];
                } else {
                    $this->error(__('网络繁忙，创建订单失败！'));
                }
            }
            // 数据库事务操作
            $result = false;
            Db::startTrans();
            try {
                model('app\api\model\wanlshop\OrderAddress')->saveAll($addressList);
                model('app\api\model\wanlshop\OrderGoods')->saveAll($goodsList);
                $result = model('app\api\model\wanlshop\Pay')->saveAll($payList);
                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success('返回成功', $result);
            } else {
                // 删除错误订单
                model('app\api\model\wanlshop\Order')->where(['id' => $order->id])->delete();
                $this->error(__('订单异常，请返回重新下单'));
            }
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function confirmOrder2() {

        $request = $this->request->post();
        $order = [];

        $express_no = 99999;
        $express_name = "自提";
        $order[] = [
            'id' => $request["id"],
            'express_name' => "自提",
            'express_no' => $express_no,
            'delivertime' => time(),
            'state' => 3
        ];
        $orders = new \app\api\model\wanlshop\Order();
        //dump($order);
        $orders->saveAll($order);
        $this->success("1", 1);
    }

    /**
     * 订单状态码（方法内使用）
     *
     * @ApiSummary  (WanlShop 返回订单状态码)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    private function getOrderState($id = 0) {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $order = model('app\api\model\wanlshop\Order')
                ->where(['id' => $id, 'user_id' => $this->auth->id])
                ->find();
        return $order['state'];
    }

    /**
     * 获取运费模板和子类 内部方法
     * @param string $id  运费ID
     * @param string $weigh  商品重量
     * @param string $number  商品数量
     * @param string $city  邮递城市
     */
    private function freight($id = null, $weigh = null, $number = 0, $city = null) {
        // 运费模板
        $data = model('app\api\model\wanlshop\ShopFreight')->where('id', $id)->field('id,delivery,isdelivery,name,valuation')->find();
        $data['price'] = 0;
        // 是否包邮:0=自定义运费,1=卖家包邮
        if ($data['isdelivery'] == 0) {
            // 获取地址编码
            $area = model('app\common\model\Area')->where('name', $city)->find();
            $list = model('app\api\model\wanlshop\ShopFreightData')
                    ->where('citys', 'like', '%' . $area['id'] . '%')
                    ->where('freight_id', $id)
                    ->find();
            // 查询是否存在运费模板数据
            if (!$list) {
                $list = model('app\api\model\wanlshop\ShopFreightData')
                        ->where('freight_id', $id)
                        ->find();
            }
            // 计价方式:0=按件数,1=按重量,2=按体积  1.0.2升级 
            if ($data['valuation'] == 0) {
                if ($number <= $list['first']) {
                    $price = $list['first_fee'];
                } else {
                    $additional = $list['additional'] > 0 ? $list['additional'] : 1; //因为要更换vue后台，临时方案，为防止客户填写0
                    $price = bcadd(bcmul(ceil(($number - $list['first']) / $additional), $list['additional_fee'], 2), $list['first_fee'], 2);
                }
            } else {
                $weigh = $weigh * $number; // 订单总重量
                if ($weigh <= $list['first']) { // 如果重量小于等首重，则首重价格
                    $price = $list['first_fee'];
                } else {
                    $additional = $list['additional'] > 0 ? $list['additional'] : 1;
                    $price = bcadd(bcmul(ceil(($weigh - $list['first']) / $additional), $list['additional_fee'], 2), $list['first_fee'], 2);
                }
            }
            $data['price'] = $price;
        }
        return $data;
    }

    public function wheretogo() {
        $order_id = input("id");
        $Info = Db::name("wanlshop_order_goods")->alias("a")->join("xsh_wanlshop_goods b", "a.goods_id=b.id and a.order_id=" . $order_id)->field("b.category_id")->find();
        if ($Info["category_id"] == 105) {
            $this->success("ok", ["data" => 1]);
        } else {
            $this->success("ok", ["data" => 0]);
        }
    }

}

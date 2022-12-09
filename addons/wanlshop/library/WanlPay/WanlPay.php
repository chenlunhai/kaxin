<?php

namespace addons\wanlshop\library\WanlPay;

use app\common\library\Auth;
use think\Db;
use think\Request;
use fast\Http;
use fast\Random;
use WanlPay\Yansongda\Pay;
use WanlPay\Yansongda\Log;

/**
 * 
 * WanlPay 多终端支付
 * @author 深圳前海万联科技有限公司 <wanlshop@i36k.com> 
 * @link http://www.wanlshop.com
 * 
 * @careful 未经版权所有权人书面许可，不得自行复制到第三方系统使用、二次开发、分支、复制和商业使用！
 * @creationtime  2020年8月7日23:46:12 - 2020年8月26日05:10:09
 * @lasttime -
 * @version V0.0.1 https://pay.yanda.net.cn/docs/2.x/overview
 * */
class WanlPay {

    private $type;
    private $method;
    private $code;

    public function __construct($type = '', $method = '', $code = '') {
        $auth = Auth::instance(); // 方式
        $this->type = $type;  // 类型
        $this->method = $method; // 方式
        $this->user_id = $auth->isLogin() ? $auth->id : 0; // 用户ID
        $this->request = \think\Request::instance();
        $this->code = $code; // 小程序code
    }

    /**
     * 支付
     */
    public function pay($order_id, $source = "order") {
        if ($this->user_id == 0) {
            return ['code' => 10001, 'msg' => '用户ID不存在'];
        }
        if($this->type != 'balance'){
             return ['code' => 10001, 'msg' => '支付宝微信支付维护升级中.....'];
        }
        
        // 获取支付信息
        if ($source == "order") {

            $pay = model('app\api\model\wanlshop\Pay')
                    ->where('order_id', 'in', $order_id)
                    ->where('user_id', $this->user_id)
                    ->select();
            // 交易号
            $pay_no = '';
            // 订单号
            $order_no = [];
            // 付款金额
            $price = 0;
            $oldmoney = 0;
            $buycoupon = 0;
            foreach ($pay as $row) {
                // 总价格
                $price += $row['price'];
                $oldmoney += $row["discount_price"];
                $buycoupon += $row["usecoupon"];
                // 订单集
                $order_no[] = $row['order_no'];
                // 交易集
                $pay_no = $row['pay_no'];
            }
            // 标题
            $title = '商城订单-' . $pay_no;
            // 支付列表
            $pay_list = [];
            // 订单列表
            $order_list = [];
        }
        // 支付方式
        if ($this->type == 'balance') {

            if ($source == "order") {
                // 判断金额
                $user = model('app\common\model\User')->get($this->user_id);
                if (!$user || $user['money'] < $price) {
                    return ['code' => 500, 'msg' => '余额不足本次支付'];
                }
                $result = false;
                $balance_no = date('YmdHis') . rand(10000000, 99999999);
                Db::startTrans();
                try {
                    foreach ($pay as $row) {
                        // 新增付款人数&新增销量
                        foreach (model('app\api\model\wanlshop\OrderGoods')->where('order_id', $row['order_id'])->select() as $goods) {
                            model('app\api\model\wanlshop\Goods')->where('id', $goods['goods_id'])->inc('payment')->inc('sales', $goods['number'])->update();
                        }
                        // 订单列表
                        $order_list[] = ['id' => $row['order_id'], 'state' => 2, 'paymenttime' => time(), "pay_status" => 1];
                        // 支付列表
                        $pay_list[] = [
                            'id' => $row['id'],
                            'trade_no' => $balance_no, // 第三方交易号
                            'pay_type' => 0, // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                            'pay_state' => 1, // 支付状态 (支付回调):0=未支付,1=已支付,2=已退款
                            'total_amount' => $price, // 总金额
                            'actual_payment' => $row['price'], // 实际支付
                            'notice' => json_encode([
                                'type' => $this->type,
                                'user_id' => $user['id'],
                                'trade_no' => $balance_no,
                                'out_trade_no' => $row['pay_no'],
                                'amount' => $row['price'],
                                'total_amount' => $price,
                                'order_id' => $row['order_id'],
                                'trade_type' => 'BALANCE',
                                'version' => '1.0.0'
                            ])
                        ];
                    }
                    // 更新支付列表
                    $result = model('app\api\model\wanlshop\Pay')->saveAll($pay_list);
                    // 更新订单列表
                    $result = model('app\api\model\wanlshop\Order')->saveAll($order_list);
                    // 修改用户金额
                    if (count($order_no) == 1) {

                        $result = self::money(-$price, $user['id'], "余额支付订单#0", 'pay', implode(",", $order_no));
                    } else {
                        $result = self::money(-$price, $user['id'], "余额合并支付订单#1", 'pay', implode(",", $order_no));
                    }
                    //    \app\api\controller\wanlshop\Teamwork::UpdateUserGrade($order_list[0]["id"]);
                    $c = new \app\api\controller\wanlshop\Teamwork();
                    $c->jdb($pay_list[0]["id"]);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    return ['code' => 10002, 'msg' => $e->getMessage()];
                }
                // 返回结果
                if ($result !== false) {
                    return ['code' => 200, 'msg' => '成功', 'data' => []];
                } else {
                    return ['code' => 10003, 'msg' => '支付异常'];
                }
            } else {
                $buyInfo = Db::name("dm_buy")->where(["id" => $order_id])->order("id desc")->find();
                //$dm_price = \app\api\controller\Tools::getConfig1("Red_Price");
                $price = $buyInfo["buynum"] * 1.088; // 调用支付
                $user = model('app\common\model\User')->get($this->auth->id);
                if (!$user || $user['money'] < $price) {
                    return ['code' => 500, 'msg' => '余额不足本次支付'];
                }
                $result = false;
                $enoughDM = Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->find();
                if ($enoughDM["salenum"] >= $buyInfo["buynum"]) {
                    Db::startTrans();
                    try {
                        // 修改用户金额

                        $saleInfo = Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->find();
                        $result = self::money(-$price, $buyInfo["buyer"], "余额支付DM订单#0", 'pay', "buy_" . time() . "_" . $buyid);
                        self::money($price, $saleInfo["saller"], "DM卖出收益", 'pay', "buy_" . time() . "_" . $buyid);
                        Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->setDec("salenum", $buyInfo["buynum"]);

                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error(__('未查询到该出售订单'), ['code' => 10002, 'msg' => $e->getMessage()]);
                    }
                } else {
                    $this->error(__('商户可用DM不足'), ['code' => 10002]);
                }
                // 返回结果
                if ($result !== false) {
                    $this->success(__('支付成功'), ['code' => 200, 'msg' => '成功', 'data' => []]);
                } else {
                    return ['code' => 10003, 'msg' => '支付异常'];
                }
            }
            // 支付宝支付、更新数据均在回调中执行
        } else if ($this->type == 'alipay') {
            $data = [
                'pay_type'=>'alipay',
                'out_trade_no' => $pay_no,
                'total_amount' => $price,
                'subject' => $title
            ];
            try {
                $addpay = new \app\api\controller\wanlshop\Newpay();
                $data = $addpay->adpayCotent($data);;
                if ($this->method == 'app' || $this->method == 'wap') {
                    return ['code' => 200, 'msg' => '成功', 'data' => $data];
                } else {
                    return ['code' => 200, 'msg' => '成功', 'data' => $data];
                }
            } catch (\Exception $e) {
                return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
            }

            // 微信支付
        } else if ($this->type == 'wechat') {


            if ($source == "order") {
                $data = [
                    'out_trade_no' => $pay_no, // 订单号
                    'body' => $title, // 标题
                    'total_fee' => $price * 100 //付款金额 单位分
                ];
                if ($this->method == 'miniapp' || $this->method == 'mp') {
                    // 获取微信openid，前期版本仅可安全获取，后续版本优化登录逻辑
                    $config = get_addon_config('wanlshop');
                    $params = [
                        'appid' => $config['mp_weixin']['appid'],
                        'secret' => $config['mp_weixin']['appsecret'],
                        'js_code' => $this->code,
                        'grant_type' => 'authorization_code'
                    ];
                    $time = time();
                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
                    if ($result['ret']) {
                        $json = (array) json_decode($result['msg'], true);
                        $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
                        if (!$third) {
                            $third = model('app\api\model\wanlshop\Third');
                            // array_key_exists("unionid",$json)
                            if (isset($json['unionid'])) {
                                $third->unionid = $json['unionid'];
                                $third->openid = $json['openid'];
                            } else {
                                $third->openid = $json['openid'];
                            }
                            $third->access_token = $json['session_key'];
                            $third->expires_in = 7776000;
                            $third->logintime = $time;
                            $third->expiretime = $time + 7776000;
                            $third->user_id = $this->user_id;
                            $third->save();
                        }
                        $data['openid'] = $json['openid'];
                    } else {
                        return ['code' => 10005, 'msg' => '获取微信openid失败，无法支付'];
                    }
                }
                // dump($this->getConfig($this->type));
                // 开始支付
                try {
                    $wechat = Pay::wechat($this->getConfig($this->type))->{$this->method}($data);
                    if ($this->method == 'app') {
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()];
                    } else if ($this->method == 'wap') {
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()];
                    } else {
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat];
                    }
                } catch (\Exception $e) {
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }
            } else {

                $buyInfo = Db::name("dm_buy")->where(["id" => $order_id])->order("id desc")->find();
                // $dm_price = \app\api\controller\Tools::getConfig1("Red_Price");
                $price = $buyInfo["buynum"] * 1.0966; // 调用支付
                $pay_no = $buyInfo["buy_no"];
                $data = [
                    'out_trade_no' => $pay_no, // 订单号
                    'body' => '商城订单_' . $pay_no, // 标题
                    'total_fee' => intval($price * 100) //付款金额 单位分
                ];
                if ($this->method == 'miniapp' || $this->method == 'mp') {
// 获取微信openid，前期版本仅可安全获取，后续版本优化登录逻辑
                    $config = get_addon_config('wanlshop');
                    $params = [
                        'appid' => $config['mp_weixin']['appid'],
                        'secret' => $config['mp_weixin']['appsecret'],
                        'js_code' => $this->code,
                        'grant_type' => 'authorization_code'
                    ];
                    $time = time();
                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
                    if ($result['ret']) {
                        $json = (array) json_decode($result['msg'], true);
                        $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
                        if (!$third) {
                            $third = model('app\api\model\wanlshop\Third');
// array_key_exists("unionid",$json)
                            if (isset($json['unionid'])) {
                                $third->unionid = $json['unionid'];
                                $third->openid = $json['openid'];
                            } else {
                                $third->openid = $json['openid'];
                            }
                            $third->access_token = $json['session_key'];
                            $third->expires_in = 7776000;
                            $third->logintime = $time;
                            $third->expiretime = $time + 7776000;
                            $third->user_id = $user_id;
                            $third->save();
                        }
                        $data['openid'] = $json['openid'];
                    } else {
                        $this->error(__('获取微信openid失败'), ['code' => 10005, 'msg' => '获取微信openid失败，无法支付']);
                        //  return ['code' => 10005, 'msg' => '获取微信openid失败，无法支付'];]
                    }
                }
                try {
                    //dump($data);
                    $wechat = Pay::wechat($this->getConfig($this->type))->{$this->method}($data);
                    if ($this->method == 'app') {
                        // $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()]);
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()];
                    } else if ($this->method == 'wap') {
                        //$this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()]);
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()];
                    } else {
                        //$this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat]);
                        return ['code' => 200, 'msg' => '成功', 'data' => $wechat];
                    }
                } catch (\Exception $e) {
                    //  $this->error(__('获取微信openid失败'), ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()]);
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }
            }
            // 百度支付
        } else if ($this->type == 'baidu') {
            try {
                
            } catch (\Exception $e) {
                return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
            }
            // QQ支付
        } else if ($this->type == 'qq') {
            try {
                
            } catch (\Exception $e) {
                return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
            }
            // 苹果支付
        } else if ($this->type == 'apple') {
            try {
                
            } catch (\Exception $e) {
                return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
            }
        }
    }

    public function pay2($order_id, $source = "order") {
        if ($this->user_id == 0) {
            return ['code' => 10001, 'msg' => '用户ID不存在'];
        }
        // 获取支付信息
        if ($source == "order") {

            $pay = model('app\api\model\wanlshop\Pay')
                    ->where('order_id', 'in', $order_id)
                    ->where('user_id', $this->user_id)
                    ->select();
            // 交易号
            $pay_no = '';
            // 订单号
            $order_no = [];
            // 付款金额
            $price = 0;
            $oldmoney = 0;
            $buycoupon = 0;
            foreach ($pay as $row) {
                // 总价格
                $price += $row['price'];
                $oldmoney += $row["discount_price"];
                $buycoupon += $row["usecoupon"];
                // 订单集
                $order_no[] = $row['order_no'];
                // 交易集
                $pay_no = $row['pay_no'];
            }
            // 标题
            $title = '商城订单-' . $pay_no;
            // 支付列表
            $pay_list = [];
            // 订单列表
            $order_list = [];
        }
        // 支付方式
        if ($this->type == 'balance') {

            if ($source == "order") {
                // 判断金额
                $user = model('app\common\model\User')->get($this->user_id);
                if (!$user || $user['money'] < $price) {
                    return ['code' => 500, 'msg' => '余额不足本次支付'];
                }
                $result = false;
                $balance_no = date('YmdHis') . rand(10000000, 99999999);
                Db::startTrans();
                try {
                    foreach ($pay as $row) {
                        // 新增付款人数&新增销量
                        foreach (model('app\api\model\wanlshop\OrderGoods')->where('order_id', $row['order_id'])->select() as $goods) {
                            model('app\api\model\wanlshop\Goods')->where('id', $goods['goods_id'])->inc('payment')->inc('sales', $goods['number'])->update();
                        }
                        // 订单列表
                        $order_list[] = ['id' => $row['order_id'], 'state' => 7, 'paymenttime' => time(), "pay_status" => 1];
                        // 支付列表
                        $pay_list[] = [
                            'id' => $row['id'],
                            'trade_no' => $balance_no, // 第三方交易号
                            'pay_type' => 0, // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                            'pay_state' => 1, // 支付状态 (支付回调):0=未支付,1=已支付,2=已退款
                            'total_amount' => $price, // 总金额
                            'actual_payment' => $row['price'], // 实际支付
                            'notice' => json_encode([
                                'type' => $this->type,
                                'user_id' => $user['id'],
                                'trade_no' => $balance_no,
                                'out_trade_no' => $row['pay_no'],
                                'amount' => $row['price'],
                                'total_amount' => $price,
                                'order_id' => $row['order_id'],
                                'trade_type' => 'BALANCE',
                                'version' => '1.0.0'
                            ])
                        ];
                    }
                    // 更新支付列表
                    $result = model('app\api\model\wanlshop\Pay')->saveAll($pay_list);
                    // 更新订单列表
                    $result = model('app\api\model\wanlshop\Order')->saveAll($order_list);
                    // 修改用户金额
                    if (count($order_no) == 1) {

                        $result = self::money(-$price, $user['id'], "余额支付兑换订单#0", 'pay', implode(",", $order_no));
                    } else {
                        $result = self::money(-$price, $user['id'], "余额支付兑换订单#1", 'pay', implode(",", $order_no));
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    return ['code' => 10002, 'msg' => $e->getMessage()];
                }
                // 返回结果
                if ($result !== false) {
                    return ['code' => 200, 'msg' => '成功', 'data' => []];
                } else {
                    return ['code' => 10003, 'msg' => '支付异常'];
                }
            }
        } else {
            return ['code' => 10003, 'msg' => '支付异常'];
        }
    }

    public function pay3($order_id, $source = "order") {
        if ($this->user_id == 0) {
            return ['code' => 10001, 'msg' => '用户ID不存在'];
        }
        // 获取支付信息
        if ($source == "order") {

            $pay = model('app\api\model\wanlshop\Pay')
                    ->where('order_id', 'in', $order_id)
                    ->where('user_id', $this->user_id)
                    ->select();
            // 交易号
            $pay_no = '';
            // 订单号
            $order_no = [];
            // 付款金额
            $price = 0;
            $oldmoney = 0;
            $buycoupon = 0;
            foreach ($pay as $row) {
                // 总价格
                $price += $row['price'];
                // 订单集
                $order_no[] = $row['order_no'];
                // 交易集
                $pay_no = $row['pay_no'];
            }
            // 标题
            $title = '商城订单-' . $pay_no;
            // 支付列表
            $pay_list = [];
            // 订单列表
            $order_list = [];
        }
        // 支付方式
        if ($this->type == 'balance') {

            if ($source == "order") {
                // 判断金额
                $user = model('app\common\model\User')->get($this->user_id);
                if (!$user || $user['score'] < $price) {
                    return ['code' => 500, 'msg' => '消费积分不足本次支付'];
                }
                $result = false;
                $balance_no = date('YmdHis') . rand(10000000, 99999999);
                Db::startTrans();
                try {
                    foreach ($pay as $row) {
                        // 新增付款人数&新增销量
                        foreach (model('app\api\model\wanlshop\OrderGoods')->where('order_id', $row['order_id'])->select() as $goods) {
                            model('app\api\model\wanlshop\Goods')->where('id', $goods['goods_id'])->inc('payment')->inc('sales', $goods['number'])->update();
                        }
                        // 订单列表
                        $order_list[] = ['id' => $row['order_id'], 'state' => 2, 'paymenttime' => time(), "pay_status" => 1];
                        // 支付列表
                        $pay_list[] = [
                            'id' => $row['id'],
                            'trade_no' => $balance_no, // 第三方交易号
                            'pay_type' => 0, // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                            'pay_state' => 1, // 支付状态 (支付回调):0=未支付,1=已支付,2=已退款
                            'total_amount' => $price, // 总金额
                            'actual_payment' => $row['price'], // 实际支付
                            'notice' => json_encode([
                                'type' => $this->type,
                                'user_id' => $user['id'],
                                'trade_no' => $balance_no,
                                'out_trade_no' => $row['pay_no'],
                                'amount' => $row['price'],
                                'total_amount' => $price,
                                'order_id' => $row['order_id'],
                                'trade_type' => 'BALANCE',
                                'version' => '1.0.0'
                            ])
                        ];
                    }
                    // 更新支付列表
                    $result = model('app\api\model\wanlshop\Pay')->saveAll($pay_list);
                    // 更新订单列表
                    $result = model('app\api\model\wanlshop\Order')->saveAll($order_list);
                    // 修改用户金额
                    if (count($order_no) == 1) {

                        $result = self::score(-$price, $user['id'], "积分支付订单#0", 'pay', implode(",", $order_no), 7);
                    } else {
                        $result = self::score(-$price, $user['id'], "积分合并支付订单#1", 'pay', implode(",", $order_no), 7);
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    return ['code' => 10002, 'msg' => $e->getMessage()];
                }
                // 返回结果
                if ($result !== false) {
                    return ['code' => 200, 'msg' => '成功', 'data' => []];
                } else {
                    return ['code' => 10003, 'msg' => '支付异常'];
                }
            }
        } else {
            return ['code' => 10003, 'msg' => '支付异常'];
        }
    }

    /**
     * 支付回调
     */
    public function notify() {
        $wanlpay = Pay::{$this->type}($this->getConfig($this->type));
        try {
            $result = $wanlpay->verify();
            // 查询订单是否存在
            $pay = model('app\api\model\wanlshop\Pay')
                    ->where(['pay_no' => $result['out_trade_no']])
                    ->select();
            if (!$pay) {
                return ['code' => 10001, 'msg' => '网络异常'];
            }
            // 支付类型
            $pay_type = 8;
            $user_id = 0;
            $order_list = [];
            $order_no = [];
            $pay_list = [];
            // 总价格
            $price = 0;
            foreach ($pay as $row) {
                $price += $row['price'];
                // 订单集
                $order_no[] = $row['order_no'];
                $user_id = $row['user_id'];
            }

            // -----------------------------判断订单是否合法-----------------------------
            $config = get_addon_config('wanlshop');
            // 支付宝
            if ($this->type == 'alipay') {
                // 判断状态
                if (in_array($result['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
                    // 判断金额
                    if ($price != $result['total_amount']) {
                        return ['code' => 10002, 'msg' => '支付金额不合法'];
                    }
                    // 判断appid
                    if ($config['sdk_alipay']['app_id'] != $result['app_id']) {
                        return ['code' => 10003, 'msg' => 'APPID不合法'];
                    }
                } else {
                    return ['code' => 500, 'msg' => '支付回调失败'];
                }
                // 回调支付
                $pay_type = 2; // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                $pay_name = '支付宝';
                $trade_no = $result['trade_no'];
            } else if ($this->type == 'wechat') {
                // 判断状态
                if ($result['result_code'] == 'SUCCESS') {
                    // 判断金额
                    if ($price != ($result['total_fee'] / 100)) {
                        return ['code' => 10002, 'msg' => '支付金额不合法'];
                    }
                    // 判断商家ID
                    if ($config['sdk_qq']['mch_id'] != $result['mch_id']) {
                        return ['code' => 10004, 'msg' => '商户不合法'];
                    }
                    // H5微信支付
                    if ($result['trade_type'] == 'MWEB') {
                        if ($config['sdk_qq']['gz_appid'] != $result['appid']) {
                            return ['code' => 10005, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                    // 小程序支付
                    if ($result['trade_type'] == 'JSAPI') {
                        if ($config['mp_weixin']['appid'] != $result['appid']) {
                            return ['code' => 10006, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                    // App支付
                    if ($result['trade_type'] == 'APP') {
                        if ($config['sdk_qq']['wx_appid'] != $result['appid']) {
                            return ['code' => 10007, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                } else {
                    return ['code' => 500, 'msg' => '支付回调失败'];
                }
                // 回调支付
                $pay_type = 1; // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                $pay_name = '微信';
                $trade_no = $result['transaction_id'];
            }

            // -----------------------------支付成功，修改订单-----------------------------
            foreach ($pay as $row) {

                // 新增付款人数&新增销量 
                foreach (model('app\api\model\wanlshop\OrderGoods')->where('order_id', $row['order_id'])->select() as $goods) {
                    model('app\api\model\wanlshop\Goods')->where('id', $goods['goods_id'])->inc('payment')->inc('sales', $goods['number'])->update();
                }
                // 订单列表
                $order_list[] = ['id' => $row['order_id'], 'state' => 2, 'paymenttime' => time(), "pay_status" => 1];
                // 支付列表
                $pay_list[] = [
                    'id' => $row['id'],
                    'trade_no' => $trade_no, // 第三方交易号
                    'pay_type' => $pay_type, // 支付类型:0=余额支付,1=微信支付,2=支付宝支付
                    'pay_state' => 1, // 支付状态 (支付回调):0=未支付,1=已支付,2=已退款
                    'total_amount' => $price, // 总金额
                    'actual_payment' => $row['price'], // 实际支付
                    'notice' => json_encode($result)
                ];
            }
            // 更新支付列表
            model('app\api\model\wanlshop\Pay')->saveAll($pay_list);
            // 更新订单列表
            model('app\api\model\wanlshop\Order')->saveAll($order_list);
            //  \app\api\controller\wanlshop\Teamwork::UpdateUserGrade($order_list[0]["id"]);

            $c = new \app\api\controller\wanlshop\Teamwork();
//
            $c->jdb($pay_list[0]["id"]);
            // 支付日志
            model('app\common\model\MoneyLog')->create([
                'user_id' => $user_id,
                'money' => -$price, // 操作金额
                'memo' => $pay_name . '支付订单', // 备注
                'type' => 'pay', // 类型
                'service_ids' => implode(",", $order_no) // 业务ID
            ]);
            Log::debug('Alipay notify', $result->all());
        } catch (\Exception $e) {
            return ['code' => 10008, 'msg' => $e->getMessage()];
        }
        // 返回给支付接口
        return ['code' => 200, 'msg' => $wanlpay->success()->send()];
    }

    /**
     * 用户充值
     */
    public function recharge($price, $fullurl) {
        if ($this->user_id == 0) {
            return ['code' => 10001, 'msg' => '用户ID不存在'];
        }
        if ($price <= 0) {
            return ['code' => 10002, 'msg' => '充值金额不合法'];
        }
        // 充值订单号
        $pay_no = date("Ymdhis") . sprintf("%08d", $this->user_id) . mt_rand(1000, 9999);
        // 支付标题
        $title = '充值-' . $pay_no;
        // 生成一个订单
        $order = \app\api\model\wanlshop\RechargeOrder::create([
                    'orderid' => $pay_no,
                    'user_id' => $this->user_id,
                    'amount' => $price,
                    'payamount' => 0,
                    'paytype' => $this->type,
                    'ip' => $this->request->ip(),
                    'useragent' => substr($this->request->server('HTTP_USER_AGENT'), 0, 255),
                    'status' => 'created',
                    'fullurl' => $fullurl
        ]);

        if ($order) {
            return ['code' => 200, 'msg' => '成功'];
        } else {
            return ['code' => 10006, 'msg' => '服务器故障'];
        }
        // 获取配置
    }

    /**
     * 充值支付回调
     */
    public function notify_recharge() {
        $wanlpay = Pay::{$this->type}($this->getConfig($this->type));
        try {
            $result = $wanlpay->verify();
            // 查询订单是否存在
            $order = model('app\api\model\wanlshop\RechargeOrder')
                    ->where(['orderid' => $result['out_trade_no']])
                    ->find();
            if (!$order) {
                return ['code' => 10001, 'msg' => '支付订单不存在'];
            }
            $memo = '';
            $trade_no = '';
            // -----------------------------判断订单是否合法-----------------------------
            $config = get_addon_config('wanlshop');
            // 支付宝
            if ($this->type == 'alipay') {
                // 判断状态
                if (in_array($result['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
                    // 判断金额
                    if ($order['amount'] != $result['total_amount']) {
                        return ['code' => 10002, 'msg' => '支付金额不合法'];
                    }
                    // 判断appid
                    if ($config['sdk_alipay']['app_id'] != $result['app_id']) {
                        return ['code' => 10003, 'msg' => 'APPID不合法'];
                    }
                } else {
                    return ['code' => 500, 'msg' => '支付回调失败'];
                }
                $memo = '支付宝余额充值';
                $trade_no = $result['trade_no'];
            } else if ($this->type == 'wechat') {
                // 判断状态
                if ($result['result_code'] == 'SUCCESS') {
                    // 判断金额
                    if ($order['amount'] != ($result['total_fee'] / 100)) {
                        return ['code' => 10002, 'msg' => '支付金额不合法'];
                    }
                    // 判断商家ID
                    if ($config['sdk_qq']['mch_id'] != $result['mch_id']) {
                        return ['code' => 10004, 'msg' => '商户不合法'];
                    }
                    // H5微信支付
                    if ($result['trade_type'] == 'MWEB') {
                        if ($config['sdk_qq']['gz_appid'] != $result['appid']) {
                            return ['code' => 10005, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                    // 小程序支付
                    if ($result['trade_type'] == 'JSAPI') {
                        if ($config['mp_weixin']['appid'] != $result['appid']) {
                            return ['code' => 10006, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                    // App支付
                    if ($result['trade_type'] == 'APP') {
                        if ($config['sdk_qq']['wx_appid'] != $result['appid']) {
                            return ['code' => 10007, 'msg' => '支付类型 ' . $result['trade_type'] . ' 不合法'];
                        }
                    }
                } else {
                    return ['code' => 500, 'msg' => '支付回调失败'];
                }
                $memo = '微信余额充值';
                $trade_no = $result['transaction_id'];
            }
            // -----------------------------支付成功，修改订单-----------------------------
            if ($order['status'] == 'created') {
                $order->memo = $trade_no;
                $order->payamount = $order['amount']; // 上面已经判断过金额，可以直接使用
                $order->paytime = time();
                $order->status = 'paid';
                $order->save();
                // 更新用户金额
                self::money(+$order['amount'], $order['user_id'], $memo, 'recharge', $order['id']);
            }
            Log::debug('Alipay notify', $result->all());
        } catch (\Exception $e) {
            return ['code' => 10008, 'msg' => $e->getMessage()];
        }
        // 返回给支付接口
        return ['code' => 200, 'msg' => $wanlpay->success()->send()];
    }

    /**
     * 支付成功
     */
    public function return() {
        $wanlpay = Pay::{$this->type}($this->getConfig($this->type));
        try {
            return $wanlpay->verify();
        } catch (\Exception $e) {
            return __($e->getMessage());
        }
    }

    /**
     * 获取配置
     * @param string $type 支付类型
     * @return array|mixed
     */
    public function getConfig() {
        $config = get_addon_config('wanlshop');

        $pay_config = [];
        if ($this->type == 'alipay') {
            $pay_config = [
                'app_id' => $config['sdk_alipay']['app_id'],
                'notify_url' => $config['ini']['appurl'] . $config['sdk_alipay']['notify_url'],
                'return_url' => $config['ini']['appurl'] . $config['sdk_alipay']['return_url'],
                'ali_public_key' => $config['sdk_alipay']['ali_public_key'],
                'private_key' => $config['sdk_alipay']['private_key'],
                'log' => [// optional
                    'file' => LOG_PATH . 'wanlpay' . DS . $this->type . '-' . date("Y-m-d") . '.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http' => [// optional
                    'timeout' => 5.0,
                    'connect_timeout' => 5.0
                ],
                    // 'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
            ];
        } else if ($this->type == 'wechat') {
            $pay_config = [
                'appid' => $config['sdk_qq']['wx_appid'], // APP APPID
                'app_id' => $config['sdk_qq']['gz_appid'], // 公众号 APPID
                'miniapp_id' => $config['mp_weixin']['appid'], // 小程序 APPID
                'mch_id' => $config['sdk_qq']['mch_id'],
                'key' => $config['sdk_qq']['key'],
                'notify_url' => $config['ini']['appurl'] . $config['sdk_qq']['notify_url'],
                'log' => [// optional
                    'file' => LOG_PATH . 'wanlpay' . DS . $this->type . '-' . date("Y-m-d") . '.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http' => [// optional
                    'timeout' => 5.0,
                    'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
                ],
                    // 'mode' => 'dev',
            ];
            if ($config['sdk_qq']['pay_cert'] == 1) {
                $pay_config['cert_client'] = ADDON_PATH . 'wanlshop' . DS . 'certs' . DS . $this->type . DS . 'apiclient_cert.pem'; // optional, 退款，红包等情况时需要用到
                $pay_config['cert_key'] = ADDON_PATH . 'wanlshop' . DS . 'certs' . DS . $this->type . DS . 'apiclient_key.pem'; // optional, 退款，红包等情况时需要用到
            }
        }
        return $pay_config;
    }

    /**
     * 变更会员余额
     * @param int    $money   余额
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     * @param string $type    类型
     * @param string $ids  	  业务ID
     */
    public static function money($money, $user_id, $memo, $type = '', $ids = '', $oid = 0) {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->money;
            $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            $row = model('app\common\model\MoneyLog')->create([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'service_ids' => $ids, // 业务ID
                'oid' => $oid
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

    public static function balance($money, $user_id, $memo, $type = '', $ids = '') {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->balance;
            $after = function_exists('bcadd') ? bcadd($user->balance, $money, 2) : $user->balance + $money;
            //更新会员信息
            $user->save(['balance' => $after]);
            //写入日志
            $row = model('app\common\model\UserBalanceLog')->create([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'service_ids' => $ids // 业务ID
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

    public static function usecoupon($money, $user_id, $memo, $type = '', $ids = '') {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->buycoupon;
            $after = function_exists('bcadd') ? bcadd($user->buycoupon, $money, 2) : $user->buycoupon + $money;
            //更新会员信息
            $user->save(['buycoupon' => $after]);
            //写入日志
            $row = model('app\common\model\BuyCouponLog')->create([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'service_ids' => $ids // 业务ID
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

    public static function score($user_id, $money, $memo, $type = '', $ids = '', $source = 9) {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->score;
            $after = function_exists('bcadd') ? bcadd($user->score, $money, 2) : $user->score + $money;
            //更新会员信息
            $user->save(['score' => $after]);
            //写入日志
            $row = \app\common\model\ScoreLog::create([
                        'user_id' => $user_id,
                        'score' => $money, // 操作金额
                        'before' => $before, // 原金额
                        'after' => $after, // 增加后金额
                        'memo' => $memo, // 备注
                        'type' => $type, // 类型
                        'service_ids' => $ids, // 业务ID
                        'source' => $source,
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更积分失败'];
        }
    }

    public static function usdt($money, $user_id, $memo, $type = '') {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->usdt;
            $after = function_exists('bcadd') ? bcadd($user->usdt, $money, 2) : $user->usdt + $money;
            //更新会员信息
            $user->save(['usdt' => $after]);
            //写入日志
            $row = model('app\common\model\UsdtLog')->create([
                'uid' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'createtime' => time()
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

}

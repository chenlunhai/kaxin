<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller\wanlshop;

use app\common\controller\Api;
use \think\Db;
use app\common\library\Auth;
use think\Request;
use fast\Http;
use fast\Random;
use WanlPay\Yansongda\Pay;
use WanlPay\Yansongda\Log;
use addons\wanlshop\library\WanlPay\WanlPay;

/**
 * Description of Dollor
 *
 * @author Administrator
 */
class Dollor extends Api {

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    private $type;
    private $method;
    private $code;

//put your code here
    public function sale() {

        if ($this->request->isPost()) {
            $dm_num = $this->request->post('dmnum');
            $uinfo = \app\common\model\User::get($this->auth->id);
            if ($uinfo["dm"] >= $dm_num) {
                $cc = new Everymoney();
                $cc->Contribution(-$dm_num, $this->auth->id, "划转交易", 'Transfer', 'hz_' . time());
                $exist = Db::name("dm_sale")->where(["saller" => $this->auth->id])->find();
                if ($exist) {
                    Db::name("dm_sale")->where(["id" => $exist["id"]])->setInc("salenum", $dm_num);
                } else {
                    Db::name("dm_sale")->insert(["saller" => $this->auth->id, "salltime" => time(), "salenum" => $dm_num]);
                }
                $dm = $uinfo["dm"] - $dm_num;
                $this->success(__('提交成功'), ["dm" => $dm]);
            } else {
                $this->error(__('账户DM不足'));
            }
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function getsaleList() {
        if ($this->request->isPost()) {
            $order = $this->request->post('order');
            $list = Db::name("dm_sale")->where(["saleflag" => 0])->order("id desc")->limit(300)->select();
            $this->success(__('发送成功'), $list);
        }
        $this->error(__('非法请求'));
    }

    public function getdmlist() {
        if ($this->request->isPost()) {
            $order = $this->request->post('order');
            $list = Db::name("user_dm_log ")->where(["user_id" => $this->auth->id])->order("id desc")->limit(300)->select();
            foreach ($list as $key => $value) {
                if ($value["type"] == 'Transfer') {
                    $list[$key]["type"] = '划转';
                } elseif ($value["type"] == 'sale') {
                    $list[$key]["type"] = '出售';
                } elseif ($value["type"] == 'buy') {
                    $list[$key]["type"] = '购买';
                } else {
                    $list[$key]["type"] = '奖励';
                }
            }
            $this->success(__('发送成功'), $list);
        }
        $this->error(__('非法请求'));
    }

    public function buy() {
        if ($this->request->isPost()) {
            $dm_num = $this->request->post('dm_num');
            $saleid = $this->request->post('saleid');
            $saleInfo = Db::name("dm_sale")->where(["id" => $saleid])->find();

            if ($saleInfo) {
                if ($saleInfo["salenum"] >= $dm_num) {
                    $hadbuy = Db::name("dm_buy")->where(["buyer" => $this->auth->id])->order("id desc")->find();
                    if ($hadbuy) {
                        if (time() - $hadbuy["buytime"] <= 1800) {
                            $this->error(__('30分钟内已存在订单'));
                        }
                    }
                    $balance_no = date('YmdHis') . rand(10000000, 99999999);
                    $total = $dm_num * \app\api\controller\Tools::getConfig1("Red_Price");
                    $result = Db::name("dm_buy")->insert(["buyer" => $this->auth->id, "saleid" => $saleid, "buynum" => $dm_num, "buyteime" => time(), "buy_no" => $balance_no, 'totalmoney' => $total]);
                    $this->success(__('订单提交成功'), ["buyid" => $result, "order_sn" => $balance_no]);
                } else {
                    $this->error(__("出售交易已刷新"));
                }
            } else {
                $this->error(__('未查询到该出售订单'));
            }
        }
        $this->error(__('非法请求'));
    }

    public function buypay() {
//设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $this->type = $this->request->post('type');
            $this->code = $this->request->post('code');
            $this->method = $this->request->post('method');
            $buyid = $this->request->post('buyid');
            $user_id = $this->auth->id;
            $this->type ? $this->type : ($this->error(__('未选择支付类型')));
            $buyInfo = Db::name("dm_buy")->where(["id" => $buyid])->order("id desc")->find();
           // $dm_price = \app\api\controller\Tools::getConfig1("Red_Price");
            $price = $buyInfo["buynum"] * 1.088; // 调用支付
            if ($this->type == 'balance') {
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
            } else if ($this->type == 'alipay') {
                $data = [
                    'out_trade_no' => $pay_no,
                    'total_amount' => $price,
                    'subject' => $title
                ];
                try {
                    $alipay = Pay::alipay($this->getConfig($this->type))->{$this->method}($data);
                    if ($this->method == 'app' || $this->method == 'wap') {
                        return ['code' => 200, 'msg' => '成功', 'data' => $alipay->getContent()];
                    } else {
                        return ['code' => 200, 'msg' => '成功', 'data' => $alipay];
                    }
                } catch (\Exception $e) {
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }

// 微信支付
            } else if ($this->type == 'wechat') {

                $wanlPay = new WanlPay($this->type, $this->method, $this->code);
                $data = $wanlPay->pay($buyid,"dm");
                 if ($data['code'] == 200) {
                $this->success('ok', $data['data']);
            } else {
                $this->error($data['msg']);
            }
//                $pay_no = $buyInfo["buy_no"];
//                $data = [
//                    'out_trade_no' => $pay_no, // 订单号
//                    'body' => '商城订单_' . $pay_no, // 标题
//                    'total_fee' => intval($price * 100) //付款金额 单位分
//                ];
//                if ($this->method == 'miniapp' || $this->method == 'mp') {
//// 获取微信openid，前期版本仅可安全获取，后续版本优化登录逻辑
//                    $config = get_addon_config('wanlshop');
//                    $params = [
//                        'appid' => $config['mp_weixin']['appid'],
//                        'secret' => $config['mp_weixin']['appsecret'],
//                        'js_code' => $this->code,
//                        'grant_type' => 'authorization_code'
//                    ];
//                    $time = time();
//                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
//                    if ($result['ret']) {
//                        $json = (array) json_decode($result['msg'], true);
//                        $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
//                        if (!$third) {
//                            $third = model('app\api\model\wanlshop\Third');
//// array_key_exists("unionid",$json)
//                            if (isset($json['unionid'])) {
//                                $third->unionid = $json['unionid'];
//                                $third->openid = $json['openid'];
//                            } else {
//                                $third->openid = $json['openid'];
//                            }
//                            $third->access_token = $json['session_key'];
//                            $third->expires_in = 7776000;
//                            $third->logintime = $time;
//                            $third->expiretime = $time + 7776000;
//                            $third->user_id = $user_id;
//                            $third->save();
//                        }
//                        $data['openid'] = $json['openid'];
//                    } else {
//                        $this->error(__('获取微信openid失败'), ['code' => 10005, 'msg' => '获取微信openid失败，无法支付']);
//                        //  return ['code' => 10005, 'msg' => '获取微信openid失败，无法支付'];]
//                    }
//                }
//                try {
//                    //dump($data);
//                    $wechat = Pay::wechat($this->getConfig($this->type))->{$this->method}($data);
//                    if ($this->method == 'app') {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()]);
//                        // return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()];
//                    } else if ($this->method == 'wap') {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()]);
//                        // return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()];
//                    } else {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat]);
//                        //return ['code' => 200, 'msg' => '成功', 'data' => $wechat];
//                    }
//                } catch (\Exception $e) {
//                    $this->error(__('获取微信openid失败'), ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()]);
//                    // return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
//                }
// 百度支付
            } else {
                try {
                    
                } catch (\Exception $e) {
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }
            }
            if ($data['code'] == 200) {
                $this->success('ok', "ok");
            } else {
                $this->error("提交失败");
            }
        }
        // $this->error(__('非正常请求'));
    }

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

}

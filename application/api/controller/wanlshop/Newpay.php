<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\api\controller\wanlshop;

use app\common\controller\Api;
use think\Db;
use think\Log;

include_once dirname(__FILE__) . "/../../library/AdapaySdk/init.php";
# 加载商户的配置文件
include_once dirname(__FILE__) . "/../../library/config.php";
#
/**
 * Description of Newpay
 *
 * @author Cc
 */

class Newpay extends Api {

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    //put your code here
    protected $appid = 'app_afd7190e-7864-4ece-b56c-440a54e346cc';

    public function createMember() {

        $member = new \AdaPaySdk\Member();
        $member_params = array(
            # app_id
            'app_id' => $this->appid,
            # 用户id
            'member_id' => 'ymh_cc_user_' . time(),
        );
        $member->create($member_params);
        if ($member->isError()) {
            //失败处理
            var_dump($member->result);
        } else {
            //成功处理
            var_dump($member->result);
        }
    }

    public function settleMember() {
        $account = new \AdaPaySdk\SettleAccount();
        $account_params = array(
            'app_id' => $this->appid,
            'member_id' =>'member_id_2222',
            'channel' => 'bank_account',
            'account_info' => [
                'card_id' => '6227002920640318929',
                'card_name' => '陈伦海',
                'cert_id' => '430181198608051114',
                'cert_type' => '00',
                'tel_no' => '18273157848',
                'bank_code' => '',
                'bank_name' => '建设银行',
                'bank_acct_type' => 1,
                'prov_code' => '',
                'area_code' => '',
            ]
        );

# 创建结算账户
        $account->create($account_params);
        # 对创建结算账户结果进行处理
        if ($account->isError()) {
            //失败处理
            var_dump($account->result);
        } else {
            //成功处理
            var_dump($account->result);
        }
    }

    public function adpay() {

        $payment = new \AdaPaySdk\Payment();
        $money = input('money');
        if ($money < 0) {
            $this->error('请输入正确的充值金额');
        }
        $money = number_format($money, 2, '.', '');
# 支付设置
        $payment_params = array(
            'app_id' => 'app_afd7190e-7864-4ece-b56c-440a54e346cc',
            'order_no' => "PY_" . date("YmdHis") . rand(100000, 999999),
            'pay_channel' => 'alipay',
            //'time_expire'=> date("YmdHis", time()+86400),
            'pay_amt' => $money,
            'goods_title' => '充值',
            'goods_desc' => '快捷充值',
            'description' => 'description',
            'device_info' => ['device_p' => $_SERVER['REMOTE_ADDR']],
            'notify_url' => 'https://api.youmeihui168.cc/api/wanlshop/newpay/notify',
        );

# 发起支付
        $payment->create($payment_params);

# 对支付结果进行处理
        if ($payment->isError()) {
            //失败处理
            $this->error('请求失败', $payment->result);
        } else {
            $data = $payment->result;
            $order = \app\api\model\wanlshop\RechargeOrder::create([
                        'orderid' => $data['order_no'],
                        'user_id' => $this->auth->id,
                        'amount' => $money,
                        'payamount' => 0,
                        'paytype' => 'alipay',
                        'ip' => $this->request->ip(),
                        'useragent' => substr($this->request->server('HTTP_USER_AGENT'), 0, 255),
                        'status' => 'created',
                        'fullurl' => '',
                        'adapay_id' => $data['id']
            ]);
            //成功处理
            $this->success('请求成功', $data);
        }
    }

    public function adpayCotent($data) {

        $payment = new \AdaPaySdk\Payment();
        $money = 0.01; //$data["total_amount"];
        $money = sprintf('%.2f', $money);  // number_format($money, 2);
# 支付设置
        $payment_params = array(
            'app_id' => 'app_afd7190e-7864-4ece-b56c-440a54e346cc',
//    'app_id'=> 'app_f7841d17-8d4e-469f-82da-1c3f43c3e470',
            'order_no' => $data["out_trade_no"],
            'pay_channel' => $data['pay_type'],
            //'time_expire'=> date("YmdHis", time()+86400),
            'pay_amt' => $money,
            'goods_title' => $data["subject"],
            'goods_desc' => '商城购物',
            'description' => 'description',
            'device_info' => ['device_p' => $_SERVER['REMOTE_ADDR']],
            'notify_url' => 'https://api.youmeihui168.cc/api/wanlshop/newpay/notifyorder',
            'expend' => [
                'openid' => $data['openid']
            ]
        );
# 发起支付
        $sa = $payment->create($payment_params);
# 对支付结果进行处理
        if ($payment->isError()) {
            //失败处理
            return $payment->result;
        } else {
            $returndata = $payment->result;
            // dump($returndata);
            $order = Db::name("wanlshop_pay")->where(["pay_no" => $data["out_trade_no"]])->update(["trade_no" => $returndata['id']]);
            return $returndata;
        }
    }

    public function notify() {
        $data = $_POST;
        file_put_contents('data.txt', var_export($data, 1));
        if ($data['type'] == 'payment.succeeded') {
            $info = json_decode($data['data']);
            $info = get_object_vars($info);
            $recharge_order = Db::name('recharge_order')->where('adapay_id', $info['id'])->find();
            file_put_contents('order.txt', var_export($recharge_order, 1));
            file_put_contents('$info.txt', var_export($info, 1));
            if (!$recharge_order) {
                Log::debug('Alipay notify', $data);
            }
            Db::name('recharge_order')->where('adapay_id', $info['id'])->update(['status' => 'paid', 'updatetime' => time()]);

            \addons\wanlshop\library\WanlPay\WanlPay::money($recharge_order['amount'], $recharge_order['user_id'], '在线充值', 0, '');
        }
    }

    public function notifyorder() {
        $data = $_POST;
        file_put_contents('data.txt', var_export($data, 1));
        if ($data['type'] == 'payment.succeeded') {
            $info = json_decode($data['data']);
            $info = get_object_vars($info);
            $recharge_order = Db::name('wanlshop_pay')->where('trade_no', $info['id'])->find();
            file_put_contents('order.txt', var_export($recharge_order, 1));
            file_put_contents('$info.txt', var_export($info, 1));
            if (!$recharge_order) {
                Log::debug('Alipay notify', $data);
            }
            Db::name('wanlshop_pay')->where('trade_no', $info['id'])->update(["pay_type" => '2', 'actual_payment' => $recharge_order["price"], 'total_amount' => $recharge_order["price"], 'pay_state' => 1]);
            Db::name('wanlshop_order')->where('id', $recharge_order['order_id'])->update(['state' => 2, 'paymenttime' => time(), "pay_status" => 1]);
            $tw = new Teamwork();
            $tw->jdb($recharge_order["id"]);
        }
    }

    public function getRecharge() {
        $recharge_order = Db::name('recharge_order')->where('adapay_id', input('id'))->find();
        if ($recharge_order) {
            $this->success('请求成功', $recharge_order);
        }
    }

    public function getorder() {
        $recharge_order = Db::name('wanlshop_pay')->where('trade_no', input('id'))->find();
        if ($recharge_order) {
            $this->success('请求成功', $recharge_order);
        }
    }

    public function getorder_wechat() {
        $orderInfo = Db::name("wanlshop_order")->where(["order_no" => str_replace('订单号：', '', input("order_no"))])->find();
        $payInfo = Db::name("wanlshop_pay")->where(["order_id" => $orderInfo["id"]])->find();
        if ($payInfo) {
            $this->success('请求成功', $payInfo);
        }
    }

}

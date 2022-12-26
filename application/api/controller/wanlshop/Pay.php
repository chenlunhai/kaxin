<?php

namespace app\api\controller\wanlshop;

use addons\wanlshop\library\WanlPay\WanlPay;
use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * WanlShop支付接口
 */
class Pay extends Api {

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 获取支付信息
     *
     * @ApiSummary  (WanlShop 获取支付信息)
     * @ApiMethod   (POST)
     * 
     * @param string $id 订单ID
     */
    public function getPay() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post('order_id');
            $id ? $id : ($this->error(__('非法请求')));
            // 判断权限
            $orderState = model('app\api\model\wanlshop\Order')
                    ->where(['id' => $id, 'user_id' => $this->auth->id])
                    ->find();
            $orderState['state'] != 1 ? ($this->error(__('订单异常'))) : '';
            // 获取支付信息
            $pay = model('app\api\model\wanlshop\Pay')
                    ->where('order_id', $id)
                    ->field('id,order_id,order_no,pay_no,price')
                    ->find();
            $this->success('ok', $pay);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 支付订单
     *
     * @ApiSummary  (WanlShop 支付订单)
     * @ApiMethod   (POST)
     * 
     * @param string $order_id 订单ID
     * @param string $type 支付类型
     */
    public function payment() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $order_id = $this->request->post('order_id/a');
            $order_id ? $order_id : ($this->error(__('非法请求')));
            $type = $this->request->post('type');
            $method = $this->request->post('method');
            $code = $this->request->post('code');
            $user_id = $this->auth->id;
            $type ? $type : ($this->error(__('未选择支付类型')));
            // 判断权限
            $order = model('app\api\model\wanlshop\Order')
                    ->where('id', 'in', $order_id)
                    ->where('user_id', $user_id)
                    ->select();
            if (!$order) {
                $this->error(__('没有找到任何要支付的订单'));
            }
            foreach ($order as $item) {
                if ($item['state'] != 1) {
                    $this->error(__('订单已支付，或网络繁忙'));
                }
            }

            $getInfo = Cache::get("order_payment_" . $user_id . "_" . $order_id[0]);
            if ($getInfo) {
                $this->error(__('请稍后再试,订单支付中...'));
            } else {
                Cache::set("order_payment_" . $user_id . "_" . $order_id[0], time());
            }
            // 调用支付
            $wanlPay = new WanlPay($type, $method, $code);
            $data = $wanlPay->pay($order_id);
            Cache::rm("order_payment_" . $user_id . "_" . $order_id[0]);
            if ($data['code'] == 200) {
                $this->success('ok', $data['data']);
            } else {
                $this->error($data['msg']);
            }
        }
        $this->error(__('非正常请求'));
    }

    public function payment2() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $order_id = $this->request->post('order_id/a');
            $order_id ? $order_id : ($this->error(__('非法请求')));
            $type = $this->request->post('type');
            $method = $this->request->post('method');
            $code = $this->request->post('code');
            $wuliu = $this->request->post('wuliu');
            $user_id = $this->auth->id;
            $type ? $type : ($this->error(__('未选择支付类型')));
            $account = \app\api\model\wanlshop\PayAccount::where(['user_id' => $this->auth->id])->find();
            if (!$account and $wuliu == 0) {
                $this->error(__('自提寄售请先补充银行卡信息'));
            }
            // 判断权限
            $order = model('app\api\model\wanlshop\Order')
                    ->where('id', 'in', $order_id)
                    ->where('user_id', $user_id)
                    ->select();

            if (!$order) {
                $this->error(__('没有找到任何要支付的订单'));
            }
            foreach ($order as $item) {
                if ($item['state'] != 1) {
                    $this->error(__('订单已支付，或网络繁忙'));
                }
            }
            // 调用支付
            $getInfo = Cache::get("order_payment2_" . $user_id . "_" . $order_id[0]);
            if ($getInfo) {
                $this->error(__('订单支付中...请稍后再试'));
            } else {
                Cache::set("order_payment2_" . $user_id . "_" . $order_id[0], time());
            }
            $wanlPay = new WanlPay($type, $method, $code);
            $data = $wanlPay->pay2($order_id);
            Cache::rm("order_payment2_" . $user_id . "_" . $order_id[0]);
            if ($data['code'] == 200) {
                if ($wuliu == 0) {
                    $pay = model('app\api\model\wanlshop\Pay')
                            ->where('order_id', 'in', $order_id)
                            ->where('user_id', $this->auth->id)
                            ->select();
                    $price = 0;
                    foreach ($pay as $row) {
                        // 总价格
                        $price += $row['price'];
                    }
                    $this->withdrawchange($price, $this->auth->id, $account, $order_id);
                }
                if ($wuliu == 0 or $wuliu == 2) {

                    model('app\api\model\wanlshop\Order')
                            ->where('id', 'in', $order_id)
                            ->where('user_id', $user_id)->update(["state" => 7]);
                }

                $this->success('ok', $data['data']);
            } else {
                $this->error($data['msg']);
            }
        }
        $this->error(__('非正常请求'));
    }

    public function payment3() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $order_id = $this->request->post('order_id/a');
            $order_id ? $order_id : ($this->error(__('非法请求')));
            $type = $this->request->post('type');
            $method = $this->request->post('method');
            $code = $this->request->post('code');
            $wuliu = $this->request->post('wuliu');
            $user_id = $this->auth->id;
            $type ? $type : ($this->error(__('未选择支付类型')));
            $account = \app\api\model\wanlshop\PayAccount::where(['user_id' => $this->auth->id])->find();
            if (!$account and $wuliu == 0) {
                $this->error(__('自提寄售请先补充银行卡信息'));
            }
            // 判断权限
            $order = model('app\api\model\wanlshop\Order')
                    ->where('id', 'in', $order_id)
                    ->where('user_id', $user_id)
                    ->select();

            if (!$order) {
                $this->error(__('没有找到任何要支付的订单'));
            }
            foreach ($order as $item) {
                if ($item['state'] != 1) {
                    $this->error(__('订单已支付，或网络繁忙'));
                }
            }
            // 调用支付
            $getInfo = Cache::get("order_payment3_" . $user_id . "_" . $order_id[0]);
            if ($getInfo) {
                $this->error(__('订单支付中...请稍后再试'));
            } else {
                Cache::set("order_payment3_" . $user_id . "_" . $order_id[0], time());
            }
            $wanlPay = new WanlPay($type, $method, $code);
            $data = $wanlPay->pay3($order_id);
            Cache::rm("order_payment3_" . $user_id . "_" . $order_id[0]);
//            if ($data['code'] == 200) {
//                if ($wuliu == 0) {
//                    $pay = model('app\api\model\wanlshop\Pay')
//                            ->where('order_id', 'in', $order_id)
//                            ->where('user_id', $this->auth->id)
//                            ->select();
//                    $price = 0;
//                    foreach ($pay as $row) {
//                        // 总价格
//                        $price += $row['price'];
//                    }
//                    $this->withdrawchange($price, $this->auth->id, $account);
//                }
//                if ($wuliu == 0 or $wuliu == 2) {
//
//                    model('app\api\model\wanlshop\Order')
//                            ->where('id', 'in', $order_id)
//                            ->where('user_id', $user_id)->update(["state" => 4]);
//                }
//
//                $this->success('ok', $data['data']);
//            } else {
//                $this->error($data['msg']);
//            }
        }
        $this->error(__('非正常请求'));
    }

    /**
     * 用户充值
     */
    public function recharge() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $money = $this->request->post('money');
            $type = $this->request->post('type');
            $method = $this->request->post('method');
            $code = $this->request->post('code');
            $fullurl = $this->request->post('fullurl');
            $user_id = $this->auth->id;
            $type ? $type : ($this->error(__('未选择支付类型')));
            $money ? $money : ($this->error(__('为输入充值金额')));
            // 调用支付
            $wanlPay = new WanlPay($type, $method, $code);
            $data = $wanlPay->recharge($money, $fullurl);
            if ($data['code'] == 200) {
                $this->success('ok', "ok");
            } else {
                $this->error("提交失败");
            }
        }
        $this->error(__('非正常请求'));
    }

    /**
     * 用户提现账户
     */
    public function getPayAccount() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $row = model('app\api\model\wanlshop\PayAccount')
                    ->where(['user_id' => $this->auth->id])
                    ->order('createtime desc')
                    ->select();
            $this->success('ok', $row);
        }
        $this->error(__('非正常请求'));
    }

    /**
     * 新增提现账户
     */
    public function addPayAccount() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['user_id'] = $this->auth->id;
            $row = model('app\api\model\wanlshop\PayAccount')->allowField(true)->save($post);
            if ($row) {
                $this->success('ok', $row);
            } else {
                $this->error(__('新增失败'));
            }
        }
        $this->error(__('非正常请求'));
    }

    /**
     * 删除提现账户
     */
    public function delPayAccount($ids = '') {
        $this->error(__('绑定银行卡不可以删除'));
        $row = model('app\api\model\wanlshop\PayAccount')
                ->where('id', 'in', $ids)
                ->where(['user_id' => $this->auth->id])
                ->delete();
        if ($row) {
            $this->success('ok', $row);
        } else {
            $this->error(__('删除失败'));
        }
    }

    /**
     * 初始化提现
     */
    public function initialWithdraw() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $config = get_addon_config('wanlshop');
            $bank = model('app\api\model\wanlshop\PayAccount')
                    ->where(['user_id' => $this->auth->id])
                    ->order('createtime desc')
                    ->find();
            $this->success('ok', [
                'money' => $this->auth->money,
                'servicefee' => $config['withdraw']['servicefee'],
                'extrafee'=>$config['withdraw']['extrafee'],
                'bank' => $bank
            ]);
        }
        $this->error(__('非正常请求'));
    }

    /**
     * 用户提现
     */
    public function withdraw() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            // 金额
            $money = $this->request->post('money');
            // 账户
            $account_id = $this->request->post('account_id');
            if ($money <= 0) {
                $this->error('提现金额不正确');
            }
            if ($money > $this->auth->money) {
                $this->error('提现金额超出可提现额度');
            }
            if (!$account_id) {
                $this->error("提现账户不能为空");
            }
            // 查询提现账户
            $account = \app\api\model\wanlshop\PayAccount::where(['id' => $account_id, 'user_id' => $this->auth->id])->find();
            if (!$account) {
                $this->error("提现账户不存在");
            }
            $config = get_addon_config('wanlshop');
            if ($config['withdraw']['state'] == 'N') {
                $this->error("系统该关闭提现功能，请联系平台客服");
            }
            if ($money < 5) {
                $this->error('提现金额不能低于5元');
            }
            if ($config['withdraw']['monthlimit']) {
                $count = \app\api\model\wanlshop\Withdraw::where('user_id', $this->auth->id)->whereTime('createtime', 'month')->count();
                if ($count >= $config['withdraw']['monthlimit']) {
                    $this->error("已达到本月最大可提现次数");
                }
            }


            $count = \think\Db::name("withdraw")->where(["user_id" => $this->auth->id])->order("id desc")->find();
//            if ($count) {
//                if (date("Y-m-d", $count["createtime"]) == date("Y-m-d", time())) {
//                    $this->error("每天只可提现一次！");
//                }
//            }

            //  计算提现手续费
            if ($config['withdraw']['servicefee'] && $config['withdraw']['servicefee'] > 0) {
                $servicefee=$money * $config['withdraw']['servicefee']/1000;
                $money= round($money,2);
                 $handingmoney = $money - $servicefee-$config['withdraw']['extrafee']; 
            } else {
                $servicefee = 0;
                $handingmoney = $money;
            }

        //    $handingmoney = $money;
            //$handingmoney = $money - $config['withdraw']['extrafee'];
            $account_name = $this->request->post('account_name');
            Db::startTrans();
            try {
                $data = [
                    'user_id' => $this->auth->id,
                    'money' => $handingmoney,
                    'handingfee' => $servicefee, // 手续费
                    'taxes' => $config['withdraw']['extrafee'],
                    'type' => $account['bankCode'],
                    'account' => $account['cardCode'],
                    'orderid' => date("Ymdhis") . sprintf("%08d", $this->auth->id) . mt_rand(1000, 9999),
                    'bankname' => $account["bankName"],
                    'accountname' => $account_name
                ];
                $withdraw = \app\api\model\wanlshop\Withdraw::create($data);
                $pay = new WanlPay;
                $pay->money(-$money, $this->auth->id, '申请提现', 'withdraw', $withdraw['id']);

                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('提现申请成功！请等待后台审核', $this->auth->money);
        }
        $this->error(__('非正常请求'));
    }

    public function withdrawchange($money, $uid, $account, $order_id) {
        //设置过滤方法
        // 查询提现账户
        $config = get_addon_config('wanlshop');
        $handingmoney = $money;
        $account_name = $this->request->post('account_name');
        if ($config['withdraw']['servicefee'] && $config['withdraw']['servicefee'] > 0) {
            $servicefee = number_format($money * $config['withdraw']['servicefee'] / 1000, 2);
            $handingmoney = $money - number_format($money * $config['withdraw']['servicefee'] / 1000, 2);
        } else {
            $servicefee = 0;
            $handingmoney = $money;
        }
        Db::startTrans();
        try {
            $data = [
                'user_id' => $this->auth->id,
                'money' => $handingmoney,
                'handingfee' => $servicefee, // 手续费
                'taxes' => 0, //number_format($money * $config['withdraw']['servicefee'] / 1000, 2),
                'type' => $account['bankCode'],
                'account' => $account['cardCode'],
                'orderid' => date("Ymdhis") . sprintf("%08d", $this->auth->id) . mt_rand(1000, 9999),
                'fromorder' => $order_id[0]
//                'bankname' => $account["bankName"],
//                'accountname' => $account_name
            ];
            $withdraw = \app\api\model\wanlshop\Withdraw::create($data);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取支付日志
     */
    public function withdrawLog() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $list = model('app\api\model\wanlshop\Withdraw')
                    ->where('user_id', $this->auth->id)
                    ->order('createtime desc')
                    ->paginate();
            $this->success('ok', $list);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取支付日志
     */
    public function moneyLog() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $list = model('app\common\model\MoneyLog')
                    ->where('user_id', $this->auth->id)
                    ->order('createtime desc')
                    ->paginate();
            $this->success('ok', $list);
        }
        $this->error(__('非法请求'));
    }

    public function balanceLog() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
//            $list = model('app\common\model\UserBalanceLog')
//                    ->where('user_id', $this->auth->id)
//                    ->order('createtime desc')
//                    ->paginate();
            $list = Db::table("static_instance")->where(["user_id" => $this->auth->id])->order("id desc")->select();

            $this->success('ok', $list);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取支付详情
     */
    public function details($id = null, $type = null) {
        if ($type == 'pay') {
            $order = model('app\api\model\wanlshop\Order')
                    ->where('order_no', 'in', $id)
                    ->where('user_id', $this->auth->id)
                    ->field('id,shop_id,createtime,paymenttime')
                    ->select();
            if (!$order) {
                $this->error(__('订单异常'));
            }
            foreach ($order as $vo) {
                $vo->pay->visible(['price', 'pay_no', 'order_no', 'order_price', 'trade_no', 'actual_payment', 'freight_price', 'discount_price', 'total_amount']);
                $vo->shop->visible(['shopname']);
                $vo->goods = model('app\api\model\wanlshop\OrderGoods')
                        ->where(['order_id' => $vo['id']])
                        ->field('id,title,difference,image,price,number')
                        ->select();
            }
            $this->success('ok', $order);
        } else if ($type == 'recharge' || $type == 'withdraw') { // 用户充值
            if ($type == 'recharge') {
                $model = model('app\api\model\wanlshop\RechargeOrder');
                $field = 'id,paytype,orderid,memo';
            } else {
                $model = model('app\api\model\wanlshop\Withdraw');
                $field = 'id,money,handingfee,status,type,account,orderid,memo,transfertime';
            }
            $row = $model
                    ->where(['id' => $id, 'user_id' => $this->auth->id])
                    ->field($field)
                    ->find();
            $this->success('ok', $row);
        } else if ($type == 'refund') {
            $order = model('app\api\model\wanlshop\Order')
                    ->where('order_no', $id)
                    ->where('user_id', $this->auth->id)
                    ->field('id,shop_id,order_no,createtime,paymenttime')
                    ->find();
            if (!$order) {
                $this->error(__('订单异常'));
            }
            $order->shop->visible(['shopname']);
            $order['refund'] = model('app\api\model\wanlshop\Refund')
                    ->where(['order_id' => $order['id'], 'user_id' => $this->auth->id])
                    ->field('id,price,type,reason,createtime,completetime')
                    ->find();
            $this->success('ok', $order);
        } else { // 系统
            $this->success('ok');
        }
    }

    public function exchange() {
        $restult = \app\api\controller\Tools::getConfig1("usdt_price");
        $uid = $this->auth->id;
        $money = $this->request->post("money");
        $uinfo = \app\common\model\User::get($uid);
        if ($uinfo["balance"] >= $money) {

            $pay = new WanlPay;
            $balance = $money / $restult;
            if ($balance >= 0) {
                $pay->usdt($balance, $this->auth->id, '余额兑换', 'exchange');
                $pay = new WanlPay;
                $pay->balance(-$money, $this->auth->id, '兑换USDT', 'exchange', "UID=>" . $uinfo["id"]);
                $this->success('ok', "兑换成功");
            } else {
                $this->error("手续费不足");
            }
        } else {
            $this->error("余额不足");
        }
    }

    public function getBankInfo() {
        $usdt_address = \app\api\controller\Tools::getConfig1("usdt_address");
        $bank = \app\api\controller\Tools::getConfig1("bankinfo");
        $r = [
            'usdt' => $usdt_address,
            'bank' => explode('/', $bank),
        ];
        $this->success('ok', $r);
    }

    /**
     * 获取usdt价格
     */
    public function getUsdt() {
        $restult = \app\api\controller\Tools::getConfig1("usdt_price");

        $this->success('ok', $restult);
    }

    public function getUserUsdt() {
        $restult = \app\api\controller\Tools::getConfig1("usdt_price");
        $uid = $this->auth->id;
        $uinfo = \app\common\model\User::get($uid);
        $this->success('ok', $uinfo["usdt"]);
    }

    /**
     * 获取余额
     */
    public function getBalance() {
        $this->success('ok', $this->auth->money);
    }

    public function getBalanc2e() {

        $remain = Db::table("static_instance")->where(["user_id" => $this->auth->id])->sum("remain_num");

        $this->success('ok', $remain);
    }

    public function getcontribute() {
        $this->success('ok', $this->auth->contribute);
    }

    public function getBalance3() {
        $this->success('ok', $this->auth->buycoupon);
    }

}

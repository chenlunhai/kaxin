<?php

namespace app\api\controller\wanlshop;

use addons\wanlshop\library\Decrypt\weixin\wxBizDataCrypt;
use addons\wanlshop\library\WanlPay\WanlPay;
use addons\wanlshop\library\WanlChat\WanlChat;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use fast\Http;
use think\Validate;
use think\Db;
use think\Cache;

/**
 * WanlShop会员接口
 */
class User extends Api {

    protected $noNeedLogin = ['login', 'logout', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'phone', 'perfect', 'importuser', 'gettoken', "vadUser"];
    protected $noNeedRight = ['*'];

    public function _initialize() {
        parent::_initialize();
        //WanlChat 即时通讯调用
        $this->wanlchat = new WanlChat();
        $this->auth->setAllowFields(['id', 'username', 'nickname', 'mobile', 'avatar', 'level', 'gender', 'birthday', 'bio', 'money', 'score', 'successions', 'maxsuccessions', 'prevtime', 'logintime', 'loginip', 'jointime']);
    }

    public function vadUser() {
        $username = input("username");
        $mobile = input("mobile");
        $order_no = input("order_no");
        $openid = input("openid");
        $uinfo = Db::name("user")->where(["username" => $username, 'mobile' => $mobile])->find();
        if (!$uinfo) {
            $this->error("未找到该用户");
        }
        $openid = "";
        if ($uinfo["openid"] != "") {
            $openid = $uinfo["openid"];
        } else {
            Db::name("user")->where(["username" => $username, 'mobile' => $mobile])->update(["openid" => $openid]);
        }

        $orderInfo = Db::name("wanlshop_order")->where(["order_no" => $order_no])->find();
        $payInfo = Db::name("wanlshop_pay")->where(["order_id" => $orderInfo["id"]])->find();
        if (true) {
            $data = [
                'pay_type' => 'wx_lite',
                'out_trade_no' => $payInfo["pay_no"],
                'total_amount' => $payInfo["price"],
                'subject' => "购物支付",
                'openid' => $openid,
            ];
            try {
                $addpay = new \app\api\controller\wanlshop\Newpay();
                $sss = $addpay->adpayCotent($data);
            } catch (\Exception $e) {
                return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
            }
            $this->success("ok", $sss);
        }
    }

    public function setbankinfo() {
        $account = \app\api\model\wanlshop\PayAccount::where(['user_id' => $this->auth->id])->find();
        if (!$account) {
            $this->error(__('兑换区请先补充银行卡信息'));
        } else {
            $this->success(__('ok'));
        }
    }

//用户token问题解决！！！
    public function gettoken() {
        $uid = $this->request->post('uid');
        $tokenInfo = \think\Db::name("user_token")->where(["user_id" => $uid])->find(); ///\app\api\model\wanlshop\Advert::get($id);
        $this->success('返回成功', $tokenInfo);
    }

    public function getqrcode() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $post = $this->request->post();

            // 获取配置
            $config = get_addon_config('wanlshop');
            // 微信小程序一键登录
            $params = [
                'appid' => $config['mp_weixin']['appid'],
                'secret' => $config['mp_weixin']['appsecret'],
                    // 'js_code' => $post['code'],
                    //  'grant_type' => 'authorization_code'
            ];
            $result = Http::sendRequest("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential", $params, 'GET');
            $json = (array) json_decode($result['msg'], true);
            //dump( $json["access_token"]);
            $p = [
                // "access_token" => $json["access_token"],
                "scene" => $post["scene"],
                "page" => $post["page"],
                'width' => 100
            ];
            $pp = json_encode($p);
            // var_dump($pp);
            $result23 = Http::sendRequest("https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $json["access_token"], $pp, 'POST');
            $filename = $this->saveTempImageByContent($result23["msg"]);
            $path = "https://jdb.maidanli.com/assets/img/" . $filename;
            $this->success(__('ok'), $path);
            //  return $result23;
            //$this->success(__('ok'), $result23);
        }
    }

    private function saveTempImageByContent($content) {
        //  $filename = __DIR__ . '/../../../public/';
        $filename = md5(base64_encode($content)) . '.jpg';
        $save_path = __DIR__ . '/../../../../public/assets/img/' . $filename;
        $fp = fopen($save_path, 'w');
        fwrite($fp, $content);
        fclose($fp);
        return $filename;
    }

    public function turn2sonBalance() {
        $user = $this->auth->getUser();
        if ($user["noturn"] == 'hidden') {
            $this->error('禁止互转！请联系管理员');
        }
        $username = input("username");
        $num = input("num");
        if ($num % 100 > 0) {
            $this->error('请转账为100的整数倍');
        }
        if (Cache::get($user["username"] . "_to_" . $username)) {
            $this->error('请等待系统响应');
        } else {
            Cache::set($user["username"] . "_to_" . $username, "start");
        }
        $toUser = Db::name("user")->where(['username' => $username])->find();
        // $toUser = \think\Db::name("user_".$t)->where(["id" => $userSub['id']])->find();
        if ($user["money"] < $num) {
            $this->error('余额不足');
        }
        if ($toUser != null) {
            $info = $user;
            \addons\wanlshop\library\WanlPay\WanlPay::money($num, $toUser["id"], ' 转入', 8, 'from_' . $info["id"]);
            \addons\wanlshop\library\WanlPay\WanlPay::money(-$num, $info["id"], ' 转出', 9, 'to_' . $toUser["id"]);
            Cache::rm($user["username"] . "_to_" . $username);
            $this->success('转出成功');
        } else {
            $this->error('用户不存在');
        }
    }

    public function importuser() {
        $p = $this->request->post('p');
        $s = $this->request->post('s');
        $aa = 0;
        if ($p != 0 or $p = 0) {
            $ex = \think\Db::name("user")->where(["mobile" => $p])->find();
            if ($ex) {
                $s_arr = explode(',', $s);
                foreach ($s_arr as $key => $value) {
                    $hadex = \think\Db::name("user")->where(["mobile" => $value])->find();
                    if (!$hadex) {
                        $ip = request()->ip();
                        $time = time();
                        $uniqid = md5(uniqid(microtime(true), true));
                        $inviteCode = substr(str_shuffle($uniqid), mt_rand(0, strlen($uniqid) - 11), 6);
                        $password = '123456';
                        $extend = [];
                        $data = [
                            'username' => $value,
                            'password' => $password,
                            'email' => '',
                            'money' => 0,
                            'score' => 0,
                            'buycoupon' => 0,
                            'mobile' => $value,
                            'level' => 0,
                            'pid' => $ex["id"],
                            'pid' => $ex["id"] . "," . $ex["pids"],
                        ];
                        $params = array_merge($data, [
                            'nickname' => substr_replace($value, '****', 3, 4),
                            'salt' => \fast\Random::alnum(),
                            'jointime' => $time,
                            'joinip' => $ip,
                            'logintime' => $time,
                            'loginip' => $ip,
                            'prevtime' => $time,
                            'status' => 'normal',
                            'recommend' => $inviteCode,
                        ]);
                        $m = explode(',', $ex["pids"]);
                        $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
                        $params = array_merge($params, $extend);
                        $user = \app\common\model\User::create($params, true);
                        \think\Db::name("user_team")->insert(["uid" => $user->id]);
                        \think\Db::name("user_team")->whereIn("uid", $m)->setInc("follow", 1);
                        \think\Db::name("user_team")->whereIn("uid", $m)->setInc("fans", 1);
                    } else {
                        $aa .= $value . "____已注册！<br/>";
                    }
                    $this->success("注册成功！", "error:" . $aa);
                }
            } else {
                if ($p == 0) {
                    $ip = request()->ip();
                    $time = time();
                    $uniqid = md5(uniqid(microtime(true), true));
                    $inviteCode = substr(str_shuffle($uniqid), mt_rand(0, strlen($uniqid) - 11), 6);
                    $password = '123456';
                    $extend = [];
                    $data = [
                        'username' => $value,
                        'password' => $password,
                        'email' => '',
                        'money' => 0,
                        'score' => 0,
                        'buycoupon' => 0,
                        'mobile' => $value,
                        'level' => 0,
                        'pid' => 0,
                        'pid' => "0,",
                    ];
                    $params = array_merge($data, [
                        'nickname' => substr_replace($value, '****', 3, 4),
                        'salt' => \fast\Random::alnum(),
                        'jointime' => $time,
                        'joinip' => $ip,
                        'logintime' => $time,
                        'loginip' => $ip,
                        'prevtime' => $time,
                        'status' => 'normal',
                        'recommend' => $inviteCode,
                    ]);
                    $m = explode(',', $ex["pids"]);

                    $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
                    $params = array_merge($params, $extend);
                    $user = \app\common\model\User::create($params, true);
                    \think\Db::name("user_team")->insert(["uid" => $user->id]);
                    \think\Db::name("user_team")->whereIn("uid", $m)->setInc("follow", 1);
                    \think\Db::name("user_team")->whereIn("uid", $m)->setInc("fans", 1);
                    $this->success("注册根节点成功！");
                } else {
                    $this->error('上级ID错误');
                }
            }
        }
    }

    public function getAdfunds() {
        $this->request->filter(['strip_tags']);
        $id = $this->request->post('aid');
        $uid = $this->request->post('uid');
        $contribute = $this->request->post('contribute');
        $user = \app\common\model\User::get($uid);
        $adInfo = \think\Db::name("wanlshop_advert")->where(["id" => $id])->find(); ///\app\api\model\wanlshop\Advert::get($id);
        $data = 0;
        if ($user->level > 0) {
            if ($adInfo["maxmoney"] > 0 and $contribute >= $user["contribute"] and $contribute > 0) {
                $money = $contribute * $adInfo["maxmoney"] / 100;
                if ($user["contribute"] - $money <= 0) {
                    $money = $user["contribute"];
                }
                if ($money > 0) {
                    $s = \think\Db::name("user_contribute_log")->where(["user_id" => $uid, 'oid' => $id])->order("id desc")->find();
                    if ($s) {
                        $had = date('Y-m-d', $s["createtime"]);
                        $now = date('Y-m-d', time());
                        if ($had == $now) {
                            $this->success('返回成功', 0);
                        }
                    }
                    \addons\wanlshop\library\WanlPay\WanlPay::money($money * 0.8, $uid, '看广告转化', 'sys', 12);
                    \app\common\model\User::score($money * 0.1, $uid, '广告奖励_增加10%积分');
                    \app\common\model\User::buycoupon($money * 0.1, $uid, '广告奖励_增加10%消费券');
                    \addons\wanlshop\library\WanlPay\WanlPay::contribute(-$money, $uid, '看广告转化', 'sys', -1, $id);
                    $cc = new Teamwork();
                    $cc->ManegeFunds($uid, $money * 0.8);
                    $data = $money;
                }
            } else {
                $this->success('返回成功', 0);
            }
        }
        $this->success('返回成功', $data);
    }

    public function agent() {
        $this->request->filter(['strip_tags']);
        $name = $this->request->post('name');
        $mobile = $this->request->post('mobile');
        $place = $this->request->post('place');
        $list = \think\Db::name("agent")->insert(["username" => $name, "mobile" => $mobile, "place" => $place]);
        $this->success('提交成功');
    }

    public function scoreDetail() {

        $this->request->filter(['strip_tags']);
        $list = \think\Db::name("user_score_log")->where(["user_id" => $this->auth->id])->order(" id", "DESC")->limit(0, 300)->select();
        $this->success('返回成功', $list);
    }

    public function MoneyDetail() {
        $this->request->filter(['strip_tags']);
        $flag = $this->request->post('type') ?? 0;
        if ($flag == 14) {
            $list = \think\Db::name("user_buycoupon_log")->where(["user_id" => $this->auth->id])->order(" id", "DESC")->limit(0, 300)->select();
        } else {
            $list = \think\Db::name("user_money_log")->where(["service_ids" => $flag, "user_id" => $this->auth->id])->order(" id", "DESC")->limit(0, 300)->select();
        }

        $this->success('返回成功', $list);
    }

    public function MyTeam() {

        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $user = $this->auth->getUser();
            //  $limit = $this->request->post('sort');
            $offset = $this->request->post('offset') ?? 0;
            $limit = $this->request->post('limit') ?? 10;
            $type = $this->request->post('type');
            $order = $this->request->post('order');
            
            $puser=\think\Db::name("user")->where(["id"=>$user["pid"]])->find();
            
            $fist = \think\Db::name("user")->where(["pid" => $user->id])->field("id,nickname,avatar,jointime,level,mobile")->limit($offset * $limit, $limit)->select();
            $fcount = \think\Db::name("user_team")->where(["uid" => $user->id])->find();
            $a = 1;
            foreach ($fist as $key => $value) {
                $teaminfo = \think\Db::name("user_team")->where(["uid" => $value["id"]])->find();
                  $fist[$key]["mobile"]= substr($value["mobile"],0,3)."****".substr($value["mobile"],7, strlen($value["mobile"]));
                $fist[$key]["xuhao"] = $a++;
                $fist[$key]["team_number"] = $teaminfo["teamnum"];
                $fist[$key]["order"] = $teaminfo["teamorder"];
                $fist[$key]["own"] = $teaminfo["own_money"];
                 $fist[$key]["active"] = $teaminfo["g1"];
            }
            $data["first"] = $fist;
            $data["share"] = count($fist);
            $data["count"] = $fcount["teamnum"];
            $data["sale"] = $fcount["teamorder"];
            $data["g1"] = $fcount["g1"];
            $data["g2"] = $fcount["g2"];
            $data["g3"] = $fcount["g3"];
            $data["g4"] = $fcount["g4"];
            $data["yes"] = $fcount["yestodayorder"];
            $data["tod"] = $fcount["todayorder"];
            $data["myorder"] = $fcount["own_money"];
             $data["puser"] = $puser;
            $this->success('返回成功', $data);
        }
        $this->error(__('非法请求'));
    }

    public function getUinfo() {

        $this->request->filter(['strip_tags']);
        $flag = $this->request->post('uid');
        $list = \think\Db::name("user")->where(["id" => $flag])->find();
        if ($list["agentcode"] != 0) {
            $city = \think\Db::name("area")->where(["id" => $list["agentcode"]])->find();
            $list["city"] = $city["name"];
        }
        $level = \think\Db::name("level_config")->select();
        foreach ($level as $key => $value) {
            if ($value["level"] == $list["level"]) {
                $list["lname"] = $value["lname"];
                break;
            } else {
                $list["lname"] = "来宾";
            }
        }
        $this->success('返回成功', $list);
    }

    public function SortOfMoney() {
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $user = $this->auth->getUser();
            $uind = \think\Db::name("user_team")->where(["uid" => $this->auth->id])->find();
            $fenhong = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 10])->sum("money") ?? 0;
            $agent_fenhong = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 31])->sum("money") ?? 0;
            $friend = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 11])->sum("money") ?? 0;
            $ad = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 12])->sum("money") ?? 0;
            $comsume = \think\Db::name("wanlshop_pay")->where(["user_id" => $this->auth->id, "pay_state" => 1])->sum("order_price");
            $internet = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 21])->sum("money") ?? 0;
            $manage = \think\Db::name("user_money_log")->where(["user_id" => $user->id, "service_ids" => 13])->sum("money") ?? 0;
            $order = \think\Db::name("fenhong")->where(["uid" => $this->auth->id])->find();
            $u_team = \think\Db::name("user_team")->where(["uid" => $this->auth->id])->find();
            $zhituivip = \think\Db::name("user")->where(["pid" => $this->auth->id])->where("level", '>', 0)->count();
            $son_info = \think\Db::name("user")->where(["pid" => $this->auth->id])->select();
            $tixian_money = \think\Db::name("withdraw")->where(["user_id" => $this->auth->id, 'status' => 'successed'])->sum("money");
            $max = $toal = 0;
            foreach ($son_info as $key => $value) {
                $s = \think\Db::name("user_team")->where(["uid" => $value["id"]])->find();
                // dump($s);
                if ($s["order_money"] + $s["own_money"] >= $max) {
                    $max = $s["order_money"] + $s["own_money"];
                }
                $toal += $s["order_money"] + $s["own_money"];
            }
            if ($zhituivip == 1) {
                $u_team["order_money"] = $max;
            }
            $list = ["fenhong" => $fenhong, "friend" => $friend, 'ad' => $ad, 'other_three' => $uind, "consume" => $comsume ?? 0, 'fenhong' => $order["total_fenhong"] ?? 0, "remain_fenhong" => $order["remain_fenhong"] ?? 0,
                "internet" => $internet, "manage" => $manage, "max" => $max, "total" => $u_team["order_money"], "zt_num" => $zhituivip, 'tixian_money' => $tixian_money, 'agent_fenhong' => $agent_fenhong,
            ];
            $this->success('返回成功', $list);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 会员登录
     * @ApiMethod   (POST)
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login() {
        //设置过滤方法

        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $account = input('account');
            $password = input('password');
            $client_id = $this->request->post('client_id');
            if (!$account || !$password) {
                $this->error(__('Invalid parameters'));
            }
            $ret = $this->auth->login($account, $password);
            if ($ret) {
                if ($client_id) {
                    $this->wanlchat->bind($client_id, $this->auth->id);
                }
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'statistics' => $this->statistics()
                ];
                $this->success(__('Logged in successful'), $data);
            } else {
                $this->error($this->auth->getError());
            }
        }
        $this->error(__('非法请求'));
    }

    /**
     * 手机验证码登录
     * @ApiMethod   (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $mobile = $this->request->post('mobile');
            $captcha = $this->request->post('captcha');
            $client_id = $this->request->post('client_id');
            $username = $this->request->post('username');
            if (!$mobile || !$captcha) {
                $this->error(__('Invalid parameters'));
            }
            // if (!Validate::regex($mobile, "^1\d{10}$")) {
            //     $this->error(__('Mobile is incorrect'));
            // }
            if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
                $this->error(__('Captcha is incorrect'));
            }
            $user = Db::name("user")->where(["mobile" => $mobile, "username" => $username])->find();

            if ($user) {
                if ($user["status"] != 'normal') {
                    $this->error(__('Account is locked'));
                }
                //如果已经有账号则直接登录
                $ret = $this->auth->direct($user["id"]);
            } else {
                $this->error(__('尚未注册'));
            }
            if ($ret) {
                Sms::flush($mobile, 'mobilelogin');
                if ($client_id) {
                    $this->wanlchat->bind($client_id, $this->auth->id);
                }
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'statistics' => $this->statistics()
                ];
                $this->success(__('Logged in successful'), $data);
            } else {
                $this->error($this->auth->getError());
            }
        }
        $this->error(__('非法请求'));
    }

      public function getUserTeam() {

        $data = $this->auth->getUserinfo();
        $this->success('返回成功', $data);
    }

    /**
     * 手机号登录
     * @ApiMethod   (POST)
     * @param string $encryptedData  
     * @param string $iv  
     */
    public function phone() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if (!isset($post['iv'])) {
                $this->error(__('获取手机号异常'));
            }
            // 获取配置
            $config = get_addon_config('wanlshop');
            // 微信小程序一键登录
            $params = [
                'appid' => 'wx45d383083f179b95',
                'secret' => '5823dd0e671988991f44bedde0e27733',
                'js_code' => $post['code'],
                'grant_type' => 'authorization_code'
            ];
            $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
            $json = (array) json_decode($result['msg'], true);

            //dump($json);
            $third = \think\Db::name("user")->where(["openid" => $json["openid"]])->find();
            $decrypt = new wxBizDataCrypt($config['mp_weixin']['appid'], $json['session_key']);
            $decrypt->decryptData($post['encryptedData'], $post['iv'], $data);
            $data = (array) json_decode($data, true);
            // 开始登录
            $mobile = $data['phoneNumber'];
            if ($mobile) {
                $user = \app\common\model\User::getByMobile($mobile);
                $exist = \think\Db::name("user")->where(["mobile" => $mobile])->find();
                $user = \app\common\model\User::getByMobile($mobile);
                // dump($user);
                $this->auth->direct($user->id);
                if (!$exist) {
                    //dump($data);
                    \think\Db::name("user")->where(["id" => $this->auth->id])->update(["mobile" => $mobile]);
                    $this->success(__('Logged in successful'), $exist);
                } else {
                    $this->success(__('Logged in successful'), $exist);
                    ;
                }
            } else {
                $this->success(__('手机号绑定失败'));
            }
//            if ($exist) {
//                //var_dump($mobile);
//                if ($third && $third['id'] != 0) {
//                    //如果已经有账号则直接登录
//                    if ($third["mobile"] == null) {
//                        \think\Db::name("user")->where(["openid" => $json["openid"]])->update(["mobile" => $mobile]);
//                    }
//
//                    $ret = $this->auth->direct($third['id']);
//                } else {
//                    // 手机号解码
//                    $user = \app\common\model\User::getByMobile($mobile);
//                    if ($user) {
//                        if ($user->status != 'normal') {
//                            $this->error(__('Account is locked'));
//                        }
//                        //如果已经有账号则直接登录
//                        $ret = $this->auth->direct($user->id);
//                    } else {
//                        $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
//                        $user = $this->auth->getUser();
//                        $user->openid = $json['openid'];
//                        $user->save();
//                    }
//                }
//
//                if ($ret) {
//                    if (isset($post['client_id']) && $post['client_id'] != null) {
//                        $this->wanlchat->bind($post['client_id'], $this->auth->id);
//                    }
//                    $data = [
//                        'userinfo' => $this->auth->getUserinfo(),
//                        'statistics' => $this->statistics()
//                    ];
//                    $this->success(__('Logged in successful'), $data);
//                } else {
//                    $this->error($this->auth->getError());
//                }
//            } else {
//                $this->error(__('暂未开放'));
//            }
        }
        //. $this->error(__('非法请求'));
    }

    /**
     * 注册会员
     * @ApiMethod   (POST)
     * @param string $mobile   手机号
     * @param string $code   验证码
     */
    public function register() {
        //设置过滤方法

        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $mobile = input('mobile');
            $account = input("account");
            $code = input('captcha');
            $client_id = input('client_id');
            $inviteCode = input('recommend');
            $password = input("password");
            if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
//            $ret = Sms::check($mobile, $code, 'register');
//            if (!$ret) {
//                $this->error(__('Captcha is incorrect'));
//            }

            $pids = $this->auth->existParent($inviteCode);
            if ($pids == 0) {
                $this->error(__('邀请码错误'));
            }
            $ret = $this->auth->register($account, $password, '', $mobile, [], $pids);
            if ($ret) {
                if ($client_id) {
                    $this->wanlchat->bind($client_id, $this->auth->id);
                }
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'statistics' => $this->statistics()
                ];
                $this->success(__('Sign up successful'), $data);
            } else {
                $this->error($this->auth->getError());
            }
        }
        $this->error(__('非法请求'));
    }

    /**
     * 注销登录
     */
    public function logout($client_id = null) {
        // 踢出即时通讯
        if ($client_id) {
            $this->wanlchat->destoryClient($client_id);
        }
        // 退出登录
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     * @ApiMethod   (POST)
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            //$user = $this->auth->getUser();
            $uid = $this->request->post("uid");
            $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
            if ($avatar) {
                $data["avatar"] = $avatar;
                //$avatar;
            } else {
                $username = $this->request->post('username');
                $nickname = $this->request->post('nickname');
                $email = $this->request->post('email');
                $bio = $this->request->post('bio');
                if ($username) {
//                    $exists = Db::name("user")->where(["username" => $username])->find(); 
//                    if ($exists) {
//                        $this->error(__('Username already exists'));
//                    }
                    $existsemail = Db::name("user")->where(["email" => $email])->find();
                    if ($existsemail) {
                        $this->error(__('email已存在'));
                    }
                    //  $data["username"] = $username;
                }
                //$data["nickname"] = $nickname;
                $data["email"] = $email;
                $data["bio"] = $bio;
            }
            Db::name("user")->where('id', $uid)->update($data);
            $user = Db::name("user")->where('id', $uid)->find();
            //$user->save();
            $this->success('返回成功', $user);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 修改手机号
     * @ApiMethod   (POST)
     * @param string $email   手机号
     * @param string $captcha 验证码
     */
    public function changemobile() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $user = $this->auth->getUser();
            $mobile = $this->request->request('mobile');
            $captcha = $this->request->request('captcha');
            if (!$mobile || !$captcha) {
                $this->error(__('Invalid parameters'));
            }
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
                $this->error(__('Mobile already exists'));
            }
            $result = Sms::check($mobile, $captcha, 'changemobile');
            if (!$result) {
                $this->error(__('Captcha is incorrect'));
            }
            $verification = $user->verification;
            $verification->mobile = 1;
            $user->verification = $verification;
            $user->mobile = $mobile;
            $user->save();

            Sms::flush($mobile, 'changemobile');
            $this->success();
        }
        $this->error(__('非法请求'));
    }

    /**
     * 重置密码
     * @ApiMethod   (POST)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $mobile = $this->request->post("email");
            $newpassword = $this->request->post("newpassword");
            $captcha = $this->request->post("captcha");
            $username = $this->request->post("username");
            if (!$newpassword || !$captcha || !$mobile || !$username) {
                $this->error(__('Invalid parameters'));
            }
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = Db::name("user")->where(["username" => $username, "mobile" => $mobile])->find();
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
            //模拟一次登录
            $this->auth->direct($user["id"]);
            $ret = $this->auth->changepwd($newpassword, '', true);
            if ($ret) {
                $this->success(__('Reset password successful'));
            } else {
                $this->error($this->auth->getError());
            }
        }
        $this->error(__('非法请求'));
    }

    public function resetpwdemail() {
//设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $mobile = $this->request->post("email");
            $newpassword = $this->request->post("newpassword");
            $captcha = $this->request->post("captcha");
            if (!$newpassword || !$captcha || !$mobile) {
                $this->error(__('Invalid parameters'));
            }
            if (!Validate::regex($mobile, "/^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/")) {
                $this->error(__('email is incorrect'));
            }
            $user = Db::name("user")->where(["email" => $mobile])->find();   // \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Cache::rm($mobile);
            $this->auth->direct($user["id"]);
            $ret = $this->auth->changepwd($newpassword, '', true);
            if ($ret) {
                $this->success(__('Reset password successful'));
            } else {
                $this->error($this->auth->getError());
            }
        }
        $this->error(__('非法请求'));
    }

    /**
     * 第三方登录-web登录
     * @ApiMethod   (POST)
     * @param string $platform 平台名称
     */
    public function third_web() {
        $this->error(__('暂未开放'));
    }

    /**
     * 第三方登录
     * @ApiMethod   (POST)
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            // 获取登录配置
            $config = get_addon_config('wanlshop');
            // 获取前端参数
            $post = $this->request->post();
            // 登录项目
            $time = time();
            $platform = $post['platform'];
            // 开始登录
            switch ($platform) {
                // 微信小程序登录
                case 'mp_weixin':
                    //  $getopenid="https://api.weixin.qq.com/sns/jscode2session?appid=wx45d383083f179b95&secret=5823dd0e671988991f44bedde0e27733&js_code=".$code."&grant_type=authorization_code";
                    $params = [
                        'appid' => 'wx45d383083f179b95',
                        'secret' => '5823dd0e671988991f44bedde0e27733',
                        'js_code' => $post['loginData']['code'],
                        'grant_type' => 'authorization_code'
                    ];

                    // dump($params);
                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');

                    if ($result['ret']) {
                        $json = (array) json_decode($result['msg'], true);
                        if (isset($json['unionid'])) {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'unionid' => $json['unionid']]);
                        } else {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
                        }
                        //           dump($third);
                        $username = $this->request->post('nickname');
                        $avatar = $this->request->post('avatar');
                        $nickname = $this->request->post('nickname');
                        // 成功登录
                        if ($third) {
                            $user1 = \think\Db::name("user")->where(["openid" => $json['openid']])->find();
                            $user = model('app\common\model\User')->get($user1["id"]);
                            $third->save([
                                'access_token' => $json['session_key'],
                                'user_id' => $user->id,
                                'expires_in' => 7776000,
                                'logintime' => $time,
                                'expiretime' => $time + 7776000
                            ]);
                            $ret = $this->auth->direct($user->id);
                            \think\Db::name("user")->where(["id" => $user->id])->update(["nickname" => $nickname, "username" => $username, "avatar" => $avatar]);
                            if ($ret) {
                                if (isset($post['client_id']) && $post['client_id'] != null) {
                                    $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                }
                                $data = [
                                    'userinfo' => $this->auth->getUserinfo(),
                                    'statistics' => $this->statistics()
                                ];
                                $this->success(__('Sign up successful'), $data);
                            } else {
                                $this->error($this->auth->getError());
                            }
                        } else {
                            // 新增$third
                            $third = model('app\api\model\wanlshop\Third');
                            $third->platform = 'weixin_open';
                            if (isset($json['unionid'])) {
                                $third->unionid = $json['unionid'];
                            } else {
                                $third->openid = $json['openid'];
                            }
                            $third->access_token = $json['session_key'];
                            $third->expires_in = 7776000;
                            $third->logintime = $time;
                            $third->expiretime = $time + 7776000;
                            $flag = 0;
                            $third->save();
                            $exist = \think\Db::name("user")->where(["openid" => $json['openid']])->find();
                            $uid = 0;
                            if (!$exist) {
                                $this->auth->ChangeRegister($username, $nickname, $avatar, $json['openid']);
                                $uid = $this->auth->id;
                                \think\Db::name("user")->where(["id" => $this->auth->id])->update(["nickname" => $nickname, "username" => $username, "avatar" => $avatar]);
                                $third->user_id = $this->auth->id;
                                $third->save();
                                $flag = 1;
                            } else {
                                \think\Db::name("user")->where(["id" => $this->auth->id])->update(["nickname" => $nickname, "username" => $username, "avatar" => $avatar]);
                                $third->user_id = $exist["id"];
                                $third->save();
                            }
                            $ret = $this->auth->direct($exist["id"]);
                            $user = $this->auth->getUser();
                            $uinfo = \think\Db::name("user")->where(["id" => $this->auth->id])->find();
                            $data = [
                                'userinfo' => $this->auth->getUserinfo(),
                                'statistics' => $this->statistics()
                            ];
                            $this->success('成功',
                                    $data
                            );
                        }
                    } else {
                        $this->error('API异常，微信小程序登录失败');
                    }
                    break;
                // 微信App登录
                case 'app_weixin':
                    $params = [
                        'access_token' => $post['loginData']['authResult']['access_token'],
                        'openid' => $post['loginData']['authResult']['openid']
                    ];
                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/userinfo", $params, 'GET');
                    if ($result['ret']) {
                        $json = (array) json_decode($result['msg'], true);
                        if (isset($json['unionid'])) {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'unionid' => $json['unionid']]);
                        } else {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
                        }
                        // 成功登录
                        if ($third) {
                            $third->save([
                                'access_token' => $post['loginData']['authResult']['access_token'],
                                'refresh_token' => $post['loginData']['authResult']['refresh_token'],
                                'expires_in' => $post['loginData']['authResult']['expires_in'],
                                'logintime' => $time,
                                'expiretime' => $time + $post['loginData']['authResult']['expires_in']
                            ]);
                            $ret = $this->auth->direct($third['user_id']);
                            if ($ret) {
                                if (isset($post['client_id']) && $post['client_id'] != null) {
                                    $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                }
                                $data = [
                                    'userinfo' => $this->auth->getUserinfo(),
                                    'statistics' => $this->statistics()
                                ];
                                $this->success(__('Sign up successful'), $data);
                            } else {
                                $this->error($this->auth->getError());
                            }
                        } else {
                            // 新增$third
                            $third = model('app\api\model\wanlshop\Third');
                            $third->platform = 'weixin_open';
                            if (isset($json['unionid'])) {
                                $third->unionid = $json['unionid'];
                            } else {
                                $third->openid = $json['openid'];
                            }
                            $third->access_token = $post['loginData']['authResult']['access_token'];
                            $third->refresh_token = $post['loginData']['authResult']['refresh_token'];
                            $third->expires_in = $post['loginData']['authResult']['expires_in'];
                            $third->logintime = $time;
                            $third->expiretime = $time + $post['loginData']['authResult']['expires_in'];
                            // 判断当前是否登录,否则注册
                            if ($this->auth->isLogin()) {
                                $third->user_id = $this->auth->id;
                                $third->save();
                                // 直接绑定自动完成
                                $this->success('绑定成功', [
                                    'binding' => 1
                                ]);
                            } else {
                                $username = $json['nickname'];
                                $mobile = '';
                                $gender = $json['sex'] == 1 ? 1 : 0;
                                $avatar = $json['headimgurl'];
                                // 注册账户        
                                $result = $this->auth->register('u_' . Random::alnum(6), Random::alnum(), '', $mobile, [
                                    'gender' => $gender,
                                    'nickname' => $username,
                                    'avatar' => $avatar
                                ]);
                                if ($result) {
                                    if (isset($post['client_id']) && $post['client_id'] != null) {
                                        $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                    }
                                    $data = [
                                        'userinfo' => $this->auth->getUserinfo(),
                                        'statistics' => $this->statistics()
                                    ];
                                    // 更新第三方登录
                                    $third->user_id = $this->auth->id;
                                    $third->openname = $username;
                                    $third->save();
                                    $this->success(__('Sign up successful'), $data);
                                } else {
                                    $this->error($this->auth->getError());
                                }
                            }
                        }
                    } else {
                        $this->error('API异常，App登录失败');
                    }
                    break;

                // 微信公众号登录
                case 'h5_weixin':
                    // 后续版本上线
                    break;

                // QQ小程序登录
                case 'mp_qq':
                    $params = [
                        'appid' => $config[$platform]['appid'],
                        'secret' => $config[$platform]['appsecret'],
                        'js_code' => $post['loginData']['code'],
                        'grant_type' => 'authorization_code'
                    ];
                    $result = Http::sendRequest("https://api.q.qq.com/sns/jscode2session", $params, 'GET');
                    if ($result['ret']) {
                        $json = (array) json_decode($result['msg'], true);
                        if (isset($json['unionid'])) {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'qq_open', 'unionid' => $json['unionid']]);
                        } else {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'qq_open', 'openid' => $json['openid']]);
                        }
                        // 成功登录
                        if ($third) {
                            $user = model('app\common\model\User')->get($third['user_id']);
                            if (!$user) {
                                $this->success('尚未绑定用户', [
                                    'binding' => 0,
                                    'third_id' => $third['id']
                                ]);
                            }
                            $third->save([
                                'access_token' => $json['session_key'],
                                'expires_in' => 7776000,
                                'logintime' => $time,
                                'expiretime' => $time + 7776000
                            ]);
                            $ret = $this->auth->direct($user->id);
                            if ($ret) {
                                if (isset($post['client_id']) && $post['client_id'] != null) {
                                    $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                }
                                $data = [
                                    'userinfo' => $this->auth->getUserinfo(),
                                    'statistics' => $this->statistics()
                                ];
                                $this->success(__('Sign up successful'), $data);
                            } else {
                                $this->error($this->auth->getError());
                            }
                        } else {
                            // 新增$third
                            $third = model('app\api\model\wanlshop\Third');
                            $third->platform = 'qq_open';
                            if (isset($json['unionid'])) {
                                $third->unionid = $json['unionid'];
                            } else {
                                $third->openid = $json['openid'];
                            }
                            $third->access_token = $json['session_key'];
                            $third->expires_in = 7776000;
                            $third->logintime = $time;
                            $third->expiretime = $time + 7776000;
                            // 判断当前是否登录
                            if ($this->auth->isLogin()) {
                                $third->user_id = $this->auth->id;
                                $third->save();
                                // 直接绑定自动完成
                                $this->success('绑定成功', [
                                    'binding' => 1
                                ]);
                            } else {
                                $third->save();
                                // 通知客户端绑定
                                $this->success('尚未绑定用户', [
                                    'binding' => 0,
                                    'third_id' => $third->id
                                ]);
                            }
                        }
                    } else {
                        $this->error('API异常，微信小程序登录失败');
                    }
                    break;

                // QQ App登录
                case 'app_qq':
                    $params = [
                        'access_token' => $post['loginData']['authResult']['access_token']
                    ];
                    $options = [
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/x-www-form-urlencoded'
                        ]
                    ];
                    $result = Http::sendRequest("https://graph.qq.com/oauth2.0/me", $params, 'GET', $options);
                    if ($result['ret']) {
                        $json = (array) json_decode(str_replace(" );", "", str_replace("callback( ", "", $result['msg'])), true);
                        if ($json['openid'] == $post['loginData']['authResult']['openid']) {
                            $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'qq_open', 'openid' => $json['openid']]);
                            if ($third) {
                                $user = model('app\common\model\User')->get($third['user_id']);
                                if (!$user) {
                                    $this->success('尚未绑定用户', [
                                        'binding' => 0,
                                        'third_id' => $third['id']
                                    ]);
                                }
                                $third->save([
                                    'access_token' => $post['loginData']['authResult']['access_token'],
                                    'expires_in' => $post['loginData']['authResult']['expires_in'],
                                    'logintime' => $time,
                                    'expiretime' => $time + $post['loginData']['authResult']['expires_in']
                                ]);
                                $ret = $this->auth->direct($third['user_id']);
                                if ($ret) {
                                    if (isset($post['client_id']) && $post['client_id'] != null) {
                                        $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                    }
                                    $data = [
                                        'userinfo' => $this->auth->getUserinfo(),
                                        'statistics' => $this->statistics()
                                    ];
                                    $this->success(__('Sign up successful'), $data);
                                } else {
                                    $this->error($this->auth->getError());
                                }
                            } else {
                                // 新增$third
                                $third = model('app\api\model\wanlshop\Third');
                                $third->platform = 'qq_open';
                                $third->openid = $json['openid'];
                                $third->access_token = $post['loginData']['authResult']['access_token'];
                                $third->expires_in = $post['loginData']['authResult']['expires_in'];
                                $third->logintime = $time;
                                $third->expiretime = $time + $post['loginData']['authResult']['expires_in'];
                                // 判断当前是否登录
                                if ($this->auth->isLogin()) {
                                    $third->user_id = $this->auth->id;
                                    $third->save();
                                    // 直接绑定自动完成
                                    $this->success('绑定成功', [
                                        'binding' => 1
                                    ]);
                                } else {
                                    $third->save();
                                    // 通知客户端绑定
                                    $this->success('尚未绑定用户', [
                                        'binding' => 0,
                                        'third_id' => $third->id
                                    ]);
                                }
                            }
                        } else {
                            $this->error(__('非法请求，机器信息已提交'));
                        }
                    } else {
                        $this->error('API异常，App登录失败');
                    }
                    break;
                // QQ 网页登录
                case 'h5_qq':
                    // 后续版本上线
                    break;
                // 微博App登录
                case 'app_weibo':
                    $params = [
                        'access_token' => $post['loginData']['authResult']['access_token']
                    ];
                    $options = [
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/x-www-form-urlencoded'
                        ],
                        CURLOPT_POSTFIELDS => http_build_query($params),
                        CURLOPT_POST => 1
                    ];
                    $result = Http::post("https://api.weibo.com/oauth2/get_token_info", $params, $options);
                    $json = (array) json_decode($result, true);
                    if ($json['uid'] == $post['loginData']['authResult']['uid']) {
                        $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weibo_open', 'openid' => $json['uid']]);
                        if ($third) {
                            $user = model('app\common\model\User')->get($third['user_id']);
                            if (!$user) {
                                $this->success('尚未绑定用户', [
                                    'binding' => 0,
                                    'third_id' => $third['id']
                                ]);
                            }
                            $third->save([
                                'access_token' => $post['loginData']['authResult']['access_token'],
                                'expires_in' => $json['expire_in'],
                                'logintime' => $json['create_at'],
                                'expiretime' => $json['create_at'] + $json['expire_in']
                            ]);
                            $ret = $this->auth->direct($third['user_id']);
                            if ($ret) {
                                if (isset($post['client_id']) && $post['client_id'] != null) {
                                    $this->wanlchat->bind($post['client_id'], $this->auth->id);
                                }
                                $data = [
                                    'userinfo' => $this->auth->getUserinfo(),
                                    'statistics' => $this->statistics()
                                ];
                                $this->success(__('Sign up successful'), $data);
                            } else {
                                $this->error($this->auth->getError());
                            }
                        } else {
                            // 新增$third
                            $third = model('app\api\model\wanlshop\Third');
                            $third->platform = 'weibo_open';
                            $third->openid = $json['uid'];
                            $third->access_token = $post['loginData']['authResult']['access_token'];
                            $third->expires_in = $json['expire_in'];
                            $third->logintime = $json['create_at'];
                            $third->expiretime = $json['create_at'] + $json['expire_in'];
                            // 判断当前是否登录
                            if ($this->auth->isLogin()) {
                                $third->user_id = $this->auth->id;
                                $third->save();
                                // 直接绑定自动完成
                                $this->success('绑定成功', [
                                    'binding' => 1
                                ]);
                            } else {
                                $third->save();
                                // 通知客户端绑定
                                $this->success('尚未绑定用户', [
                                    'binding' => 0,
                                    'third_id' => $third->id
                                ]);
                            }
                        }
                    } else {
                        $this->error(__('非法请求，机器信息已提交'));
                    }
                    break;

                // 小米App登录
                case 'app_xiaomi':

                    break;

                // 苹果登录
                case 'apple':
                    // 后续版本上线
                    break;
                default:
                    $this->error('暂并不支持此方法登录');
            }
        }
        $this->error(__('10086非正常请求'));
    }

    /**
     * 进一步完善资料
     * @ApiMethod   (POST)
     */
    public function perfect() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            // 判断third_id没有绑定
            $third = model('app\api\model\wanlshop\Third')->get($post['third_id']);
            // 当user_id 不为空可以绑定
            if ($third['user_id'] == 0 && $third) {
                $username = $post['nickName'];
                $mobile = '';
                $gender = $post['gender'];
                $avatar = $post['avatarUrl'];
                $result = $this->auth->register('u_' . Random::alnum(6), Random::alnum(), '', $mobile, [
                    'gender' => $gender,
                    'nickname' => $username,
                    'avatar' => $avatar
                ]);
                if ($result) {
                    $data = [
                        'userinfo' => $this->auth->getUserinfo(),
                        'statistics' => $this->statistics()
                    ];
                    // 更新第三方登录
                    $third->save([
                        'user_id' => $this->auth->id,
                        'openname' => $username
                    ]);
                    $this->success(__('Sign up successful'), $data);
                } else {
                    $this->error($this->auth->getError());
                }
            } else {
                $this->error(__('非法请求，机器信息已提交'));
            }
        }
        $this->error(__('非法请求'));
    }

    public function changeParents() {
        if ($this->request->post()) {
            $post = $this->request->post();
            $uid = $post["uid"];
            $user = \app\common\model\User::get($uid);
            if ($user) {
                $pid = $post["share"];
                if ($user->pid == 0) {
                    $pinfo = \app\common\model\User::get($pid);
                    $pinfo1 = \think\Db::name("user")->where(["recommend" => $pid])->find();
                    if ($pinfo and!$pinfo1) {
                        $user->pid = $pid;
                        $user->pids = $pid . "," . $pinfo["pids"];
                        $user->save();
                        $sb = $pid . "," . $pinfo["pids"];
                        $pidsss = explode(",", $sb);
                        \think\Db::name("user_team")->whereIn("uid", explode(',', $sb))->setInc("flow", 1);
                        \think\Db::name("user_team")->whereIn("uid", explode(',', $sb))->setInc("fans", 1);
                        $this->success('ok');
                    }
                    if (!$pinfo and $pinfo1) {
                        $user->pid = $pinfo1["id"];
                        $user->pids = $pinfo1["id"] . "," . $pinfo1["pids"];
                        $user->save();
                        $sb = $pinfo1["id"] . "," . $pinfo1["pids"];
                        $pidsss = explode(",", $sb);
                        \think\Db::name("user_team")->whereIn("uid", explode(',', $sb))->setInc("flow", 1);
                        \think\Db::name("user_team")->whereIn("uid", explode(',', $sb))->setInc("fans", 1);
                        $this->success('ok');
                    }
                } else {
                    $this->success('ok');
                }
            } else {
                $this->error('find error');
            }
        } else {
            $this->error('fuckcc');
        }


//        if ($this->request->post()) {
//            $post = $this->request->post();
//            $uid = $post["share"];
//            $user = \app\common\model\User::get($uid);
//            if ($user) {
//                $pid = $post["share"];
//                if ($user->pid == 0) {
//                    $pinfo = \app\common\model\User::get($pid);
//                    $pidsss = $pinfo["pids"] . $pid;
//                    if ($pinfo) {
//                        $user->pid = $pid;
//                        $user->pids = $pinfo["pids"] . $pid . ",";
//                        $user->save();
//                        \think\Db::name("user_team")->whereIn("uid", explode(',', $pidsss))->setInc("flow", 1);
//                        \think\Db::name("user_team")->whereIn("uid", explode(',', $pidsss))->setInc("fans", 1);
//                        $this->success('ok');
//                    }
//                } else {
//                    $this->error($user['id']);
//                }
//            } else {
//                $this->error('find error');
//            }
//        } else {
//            $this->error('fuckcc');
//        }
    }

    /**
     * 刷新用户中心
     * @ApiMethod   (POST)
     */
    public function refresh() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $this->success(__('刷新成功'), $this->statistics());
        }
        $this->error(__('非法请求'));
    }

    /**
     * 数据统计 - 内部使用，开发者不要调用
     */
    public function statistics() {
        $user_id = $this->auth->id;
        // 查询订单
        $order = model('app\api\model\wanlshop\Order')
                ->where('user_id', $user_id)
                ->select();
        $orderCount = array_count_values(array_column($order, 'state'));
        // 物流列表
        $logistics = [];
        foreach ($order as $value) {
            if ($value['state'] >= 3 && $value['state'] <= 6) {
                //需要查询的订单
            }
        }
        // 查询动态 、收藏夹、关注店铺、足迹、红包卡券
        $data = [
            'dynamic' => [
                'collection' => model('app\api\model\wanlshop\GoodsFollow')->where('user_id', $user_id)->count(),
                'concern' => model('app\api\model\wanlshop\ShopFollow')->where('user_id', $user_id)->count(),
                'footprint' => model('app\api\model\wanlshop\Record')->where('user_id', $user_id)->count(),
                'coupon' => model('app\api\model\wanlshop\CouponReceive')->where(['user_id' => $user_id, 'state' => '1'])->count(),
                'accountbank' => model('app\api\model\wanlshop\PayAccount')->where('user_id', $user_id)->count()
            ],
            'order' => [
                'pay' => isset($orderCount[1]) ? $orderCount[1] : 0,
                'delive' => isset($orderCount[2]) ? $orderCount[2] : 0,
                'receiving' => isset($orderCount[3]) ? $orderCount[3] : 0,
                'evaluate' => isset($orderCount[7]) ? $orderCount[7] : 0,
                'customer' => isset($orderCount[8]) ? $orderCount[8] : 0,
            ],
            'logistics' => $logistics
        ];
        return $data;
    }

    public function vadpaypass() {
        $user_id = input("id");
        $pass = input("paypss");
        $exist = Db::name("user_paytest")->where(["uid" => $user_id])->find();
        $confrimPass = $pass; //md5(md5($pass),$exist["salt"]);
        if ($exist) {
            if ($confrimPass == $exist["paypass"]) {
                $this->success('ok', '密码通过');
            } else {
                $this->error(__('支付密码错误'));
            }
        } else {
            $this->error(__('先设置支付密码'));
        }
    }

    public function setPaypass() {
        $user_id = input("id");
        $mobile = input("email");
        $pass = input("paypss");
        $captcha = $this->request->post("captcha");
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        $ret = Sms::check($mobile, $captcha, 'resetpaypwd');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        Sms::flush($mobile, 'resetpaypwd');

        $salt = Random::alnum();
        $exist = Db::name("user_paytest")->where(["uid" => $user_id])->find();
        $newpass = md5(md5($pass), $salt);
        if ($exist) {
            $confrimPass = $pass;
            if (true) {
                Db::name("user_paytest")->where(["uid" => $user_id])->update(["uid" => $user_id, "paypass" => $pass, "salt" => $salt]);
                $this->success('ok', '修改成功');
            } else {
                $this->error(__('支付密码错误'));
            }
        } else {
            //dump($newpass);
            Db::name("user_paytest")->insert(["uid" => $user_id, "paypass" => $pass, "salt" => $salt]);
            $this->success('ok', '设置成功');
        }
    }

    public function getusermoneybag() {

        $uinfo = Db::name("user")->where(["id" => $this->auth->id])->find();
        $madd = Db::name("user_money_log")->where(["user_id" => $this->auth->id])->where("money", ">", 0)->sum("money");
        $mcalc = Db::name("user_money_log")->where(["user_id" => $this->auth->id])->where("money", "<", 0)->sum("money");

        $badd = Db::table("static_instance")->where(["user_id" => $this->auth->id])->sum("total_num");
        $bcalc = Db::table("static_instance")->where(["user_id" => $this->auth->id])->sum("remain_num");
        $sadd = Db::name("user_score_log")->where(["user_id" => $this->auth->id])->where("score", ">", 0)->sum("score");
        $scalc = Db::name("user_score_log")->where(["user_id" => $this->auth->id])->where("score", "<", 0)->sum("score");

        $data = [
            "money" => $uinfo["money"],
            "madd" => $madd,
            "mcalc" => $mcalc,
           
            "badd" => $badd,
            "bcalc" => $badd - $bcalc,
            "balance" => number_format($badd - $bcalc),
            "score" => $uinfo["score"],
            "sadd" => $sadd,
            "scalc" => $scalc,
        ];
        $this->success("ok", $data);
    }

    public function addservice() {

        $mobile = input("mobile");
        $cname = input("cname");
        $address = input("address");
        $data = Db::name("user_service")->insert(["uid" => $this->auth->id, "mobile" => $mobile, "cname" => $cname, "address" => $address, "addtime" => date("Y-m-d")]);

        $this->success("提交成功", $data);
    }

    public function getservice() {
        $exist = Db::name("user_service")->where(["uid" => $this->auth->id])->find();
        if ($exist) {
            $this->success("提交成功", 1);
        } else {
            $this->success("提交成功", 0);
        }
    }

    /**
     * 获取评论列表
     *
     * @ApiSummary  (WanlShop 获取我的所有评论)
     * @ApiMethod   (GET)
     * 
     * @param string $list_rows  每页数量
     * @param string $page  当前页
     */
    public function comment() {
        $list = model('app\api\model\wanlshop\GoodsComment')
                ->where('user_id', $this->auth->id)
                ->field('id,images,score,goods_id,order_goods_id,state,content,createtime')
                ->order('createtime desc')
                ->paginate()
                ->each(function ($data, $key) {
            $data['order_goods'] = $data->order_goods ? $data->order_goods->visible(['id', 'title', 'image', 'price']) : '';
            return $data;
        });
        $this->success('返回成功', $list);
    }

    /**
     * 获取积分明细
     */
    public function scoreLog() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $list = model('app\common\model\ScoreLog')
                    ->where('user_id', $this->auth->id)
                    ->order('createtime desc')
                    ->paginate();
            $this->success('ok', $list);
        }
        $this->error(__('非法请求'));
    }

}

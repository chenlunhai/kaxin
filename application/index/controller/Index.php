<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use fast\Http;
use think\Cache;
use think\Db;

class Index extends Frontend {

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    
//    public function  lang()
//    {
//        $lang = input('?get.lang') ?  input('get.lang') : 'cn';
//        switch ($lang) {
//            //中文
//            case 'cn':
//                cookie('think_var', 'zh-cn');
//                break;
//            //英文
//            case 'tw':
//                cookie('think_var', 'zh-tw');
//                break;
//            default:
//                cookie('think_var', 'zh-cn');
//                break;
//        }
//    }

    public function Promotion() {
        echo "8888888888888";
        dump("7777777777777");
        return $this->fetch();
    }
    public function index() {


//        if ($this->request->isAjax()) {
//            $list = \think\Db::query("SELECT `order`.`id`,`order`.`user_id`,`order`.`shop_id`,`order`.`order_no`,`order`.`address_id`,`order`.`coupon_id`,`order`.`isaddress`,`order`.`freight_type`,`order`.`express_name`,`order`.`express_no`,`order`.`state`,`order`.`remarks`,`order`.`createtime`,`order`.`paymenttime`,`order`.`delivertime`,`order`.`taketime`,`order`.`dealtime`,`order`.`updatetime`,`order`.`deletetime`,`order`.`status`,`order`.`leader_id`,`order`.`goods_id`,`order`.`pay_status`,user.id AS user__id,user.group_id AS user__group_id,user.username AS user__username,user.nickname AS user__nickname,user.password AS user__password,user.salt AS user__salt,user.email AS user__email,user.mobile AS user__mobile,user.avatar AS user__avatar,user.level AS user__level,user.gender AS user__gender,user.birthday AS user__birthday,user.bio AS user__bio,user.money AS user__money,user.score AS user__score,user.successions AS user__successions,user.maxsuccessions AS user__maxsuccessions,user.prevtime AS user__prevtime,user.logintime AS user__logintime,user.loginip AS user__loginip,user.loginfailure AS user__loginfailure,user.joinip AS user__joinip,user.jointime AS user__jointime,user.createtime AS user__createtime,user.updatetime AS user__updatetime,user.token AS user__token,user.status AS user__status,user.verification AS user__verification,user.recommend AS user__recommend,user.pids AS user__pids,user.integral AS user__integral,user.total_integral AS user__total_integral,user.agentcode AS user__agentcode,user.pid AS user__pid,user.is_blacklist AS user__is_blacklist,user.usdt AS user__usdt,user.prate AS user__prate,user.cardnum AS user__cardnum,user.truename AS user__truename,user.card0 AS user__card0,user.card1 AS user__card1,user.flag AS user__flag,user.balance AS user__balance,user.buycoupon AS user__buycoupon,user.city AS user__city,user.country AS user__country,user.province AS user__province,user.grade AS user__grade,user.agent AS user__agent,user.openid AS user__openid,user.contribute AS user__contribute,user.oldid AS user__oldid,user.oldpid AS user__oldpid,user.isper AS user__isper,pay.id AS pay__id,pay.pay_no AS pay__pay_no,pay.trade_no AS pay__trade_no,pay.user_id AS pay__user_id,pay.shop_id AS pay__shop_id,pay.order_id AS pay__order_id,pay.order_no AS pay__order_no,pay.pay_type AS pay__pay_type,pay.pay_state AS pay__pay_state,pay.number AS pay__number,pay.price AS pay__price,pay.order_price AS pay__order_price,pay.freight_price AS pay__freight_price,pay.coupon_price AS pay__coupon_price,pay.discount_price AS pay__discount_price,pay.refund_price AS pay__refund_price,pay.actual_payment AS pay__actual_payment,pay.total_amount AS pay__total_amount,pay.notice AS pay__notice,pay.createtime AS pay__createtime,pay.updatetime AS pay__updatetime,pay.deletetime AS pay__deletetime,pay.status AS pay__status,pay.usecoupon AS pay__usecoupon,pay.leader_id AS pay__leader_id,pay.loop_times AS pay__loop_times,pay.loop_status AS pay__loop_status,pay.win AS pay__win,pay.goods_id AS pay__goods_id FROM `xsh_wanlshop_order` `order` LEFT JOIN `xsh_user` `user` ON `order`.`user_id`=`user`.`id` LEFT JOIN `xsh_wanlshop_pay` `pay` ON `order`.`id`=`pay`.`order_id` WHERE (  (  `order`.`shop_id` IN ('1')  AND ) ) AND `order`.`deletetime` IS NULL ORDER BY `order`.`id` DESC LIMIT 0,150");
//            foreach ($list as $key => $value) {
//                $addressInfo = \think\Db::name("wanlshop_order_address")->where(["order_id" => $value["id"]])->find();
//                if ($addressInfo) {
//                    $list[$key]["AddressInfo"] = $addressInfo;
//                }
//            }
//            return json($list);
//        }
//        dump($list);
//        $html = "<table><tr><td>序号</td><td>姓名</td><td>手机号</td><td>商品名称</td><td>数量</td><td></td><td></td><td></td><td></td>"
        // get_addon_autoload_config()
        //   $ss->fenhong_instance(2595, 100, 0, 150);
//        $System_FenHong = \think\Db::name("zconfig")->where(["key" => 'System_FenHong'])->find();
//
//        dump($System_FenHong["info"]);
//        if ($System_FenHong["info"] == 0) {
//            echo "99999999999999999999999";
//        }
//\app\api\model\wanlshop\Withdraw::where('user_id', 143414)->whereTime('createtime', 'day')->find();
        // dump($count);
        //$cc->jdb(327);
//        $config = getKuaidi('4313549186555');
//
//        $result = \GuzzleHttp\json_decode($config);
//        dump($config);
//          $sonlist = Db::name("user")->where(["pid" => 2615])->select();
//          dump($sonlist);
//            if (count($sonlist) >= 3) {
//                $hadcount = 0;
//                foreach ($sonlist as $key => $value) {
//                    $hasOrder = Db::name("user_team")->where(["uid" => $value["id"]])->find();
//                    if ($hasOrder["own_money"] >= 3000) {
//                        $hadcount += 1;
//                    }
//                }
//                
//                dump($hadcount);
//                if ($hadcount >= 3) {
//                    $exit = Db::name("user_loop")->where(["uid" => 2615])->find();
//                    if (!$exit) {
//                        dump($exit);
//                       // Db::name("user_loop")->insert(["uid" => $uInfo["pid"]]);
//                    }
//                }
//            }
//         $time=1659681275;
//         $calc = (time() - $time) / 86400;
//        $day = ($calc-1) % 9;
//        $uInfo = Db::name("user")->where(["id" => 2627])->find();
//        $uccname = $uInfo["nickname"];
//        $pids = explode(",", $uInfo["pids"]);
//        dump($pids);
//        dump($day);
//        dump(count($pids));
//        dump($pids[$day]);
//        $list=Db::table("static_instance")->where("1=1")->select();
//        foreach ($list as $k => $v) {
//               $sonlist2 = Db::name("user")->where(["pid" => $v["user_id"]])->select();
//            if (count($sonlist2) >= 3) {
//                $hadcount1 = 0;
//                foreach ($sonlist2 as $key => $value) {
//                    $hasOrder1 = Db::name("user_team")->where(["uid" => $value["id"]])->find();
//                    if ($hasOrder1["own_money"] >= 3000) {
//                        $hadcount1 += 1;
//                    }
//                }
//                if ($hadcount1 >= 3) {
//                    $exit = Db::name("user_loop")->where(["uid" => $v["user_id"]])->find();
//                    if (!$exit) {
//                        Db::name("user_loop")->insert(["uid" =>$v["user_id"]]);
//                    }
//                }
//            }
//        }
//          $test= new \app\api\controller\wanlshop\Teamwork();
//       
//       $test->UserLevel("2611,2610,2609,2607,2604,2603,2601,2600,2598,2597,2596,2595,0");
//        $sms=new \app\api\controller\wanlshop\Sms();
//        $sms->sendtest('13077338433','register');
     
        $lang=   \think\Cookie::get('think_var');
        $name= __($lang);
        $this->assign("lang", $name);
        return $this->view->fetch();
    }

    public function download() {

//        $test= new \app\api\controller\wanlshop\Teamwork();
//        $test->jdb(215);
//        dump(time());
//        dump(date("Y-m-d H:i:s"));
//        $list = Db::query("select b.* from xsh_wanlshop_order a,xsh_wanlshop_pay b where a.id=b.order_id and a.state=2 and b.pay_state='1'  and b.createtime<1660392013");
//        foreach ($list as $key => $value) {
//            $uinfo = Db::name("user")->where(["id" => $value["user_id"]])->find();
//            $pids = explode(',', $uinfo["pids"]);
//            if ($value["price"] == 3000) {
//                Db::name("user_team")->whereIn("uid", $pids)->setInc("g1", 1);
//            } elseif ($value["price"] == 5000) {
//                Db::name("user_team")->whereIn("uid", $pids)->setInc("g2", 1);
//            } elseif ($value["price"] == 15000) {
//                Db::name("user_team")->whereIn("uid", $pids)->setInc("g3", 1);
//            } elseif ($value["price"] == 30000) {
//                Db::name("user_team")->whereIn("uid", $pids)->setInc("g4", 1);
//            } else {
//                
//            }
        // }
//
//        $dosth = new \app\common\controller\Dosth();
//        $dosth->dorelease();
//        $dosth->updateday();
//        
//        $newpay = new \app\api\controller\wanlshop\Newpay();
//        $newpay->createMember();
        // return $this->view->fetch();

        $sql = "select user_id ,sum(was_num)as numwas from static_instance group by user_id";
        $list = Db::query($sql);
        foreach ($list as $key => $value) {
            $findscore = Db::name("user_score_log")->where(["user_id" => $value["user_id"]])->sum("score") / 0.2;
            if ($value["numwas"] - $findscore > 10) {
                $a=$value["numwas"] - $findscore;
                echo $value["user_id"] . "__" . $value["numwas"] . "_____" . $findscore ."__".$a."</br>";
            }
        }
    }

    public function importuser() {
        $p = $this->request->post('p');
        $s = $this->request->post('s');
        $aa = "";
        if ($p != 0 or $p == 0) {
            $ex = \think\Db::name("user")->where(["mobile" => $p])->find();
            $s_arr = explode(',', $s);
            foreach ($s_arr as $key => $value) {
                $hadex = \think\Db::name("user")->where(["mobile" => $value])->find();
                if (!$hadex and $value != 0) {
                    if ($p != 0) {
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
                            'pids' => $ex["id"] . "," . $ex["pids"],
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
                        $aa = $ex["id"] . "," . $ex["pids"];
                        $m = explode(',', $aa);
                        $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
                        $params = array_merge($params, $extend);
                        $user = \app\common\model\User::create($params, true);
                        \think\Db::name("user_team")->insert(["uid" => $user->id]);
                        \think\Db::name("user_team")->whereIn("uid", $m)->setInc("flow", 1);
                        \think\Db::name("user_team")->whereIn("uid", $m)->setInc("fans", 1);
                    } else {
                        if ($p == 0) {
                            if ($value != 0) {
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
                                $this->success("注册根节点成功！");
                            }
                        }
                    }
                } else {
                    $aa .= $value . "##";
                }
                //  
            }

            //  dump("重复号码：".$aa);
            $this->success("error:重复_" . $aa);
        }
    }

    public function import() {
        if ($this->request->isPost()) {
            $file = $this->request->file('userfile');
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filepath = ROOT_PATH . 'public' . DS . 'uploads/' . $info->getSaveName();

            $reader = new Xls();
            $insert = [];
            try {
                if (!$PHPExcel = $reader->load($filepath)) {
                    $this->error(__('Unknown data format'));
                }
                $sheet = $PHPExcel->getSheet(0);
                $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
                $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
                $allRow = $currentSheet->getHighestRow(); //取得一共有多少行

                $maxColumnNumber = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($allColumn);
                $allColumn = $sheet->getHighestColumn();        //**取得最大的列号*/
                $allRow = $sheet->getHighestRow();        //**取得一共有多少行*/
                $data = array();
                for ($rowIndex = 1; $rowIndex <= $allRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从1开始，列从A开始
                    for ($colIndex = 0; $colIndex <= $maxColumnNumber; $colIndex++) {
                        $data[$rowIndex][] = (string) $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
                    }
                }
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
        } else {
            return $this->view->fetch();
        }
    }

    public function fenhong() {

        if ($this->request->isGet()) {
            $token = $this->request->get('token');
            $ss = new \app\api\controller\wanlshop\Teamwork();
            $list = \think\Db::name("fenhong")->where("remain_fenhong", ">", 0)->select();
            $getRate = \app\api\controller\Tools::getConfig1("max_rate");
            $funds = \app\api\controller\Tools::getConfig1("Jackpot");
            $date = date('Y-m-d', time());
            $tomorrowyestoday_date = date('Y-m-d', time() - 86400);
            $yestoday = \think\Db::name("fenhong_instance")->where(["calctime" => $tomorrowyestoday_date])->find();

            if ($yestoday) {
                //昨日系统存在分红
                $fenhong = $yestoday["sys_fenhong"];

                $fhz = number_format($funds / $fenhong, 4);
                //系统设定分红值
                $System_FenHong = \think\Db::name("zconfig")->where(["key" => 'System_FenHong'])->find();
                foreach ($list as $key => $value) {

                    $today = \think\Db::name("fenhong_yestoday")->where(["uid" => $value["uid"], 'addtime' => $date])->find();
                    $not_today = \think\Db::name("fenhong_yestoday")->where(["uid" => $value["uid"]])->where("addtime", '<>', $date)->find();
                    if ($today and $value["addtime"] != $date) {
                        $value["remain_fenhong"] = $value["remain_fenhong"] - $today["money"];
                        $value["remain_money"] = $value["remain_money"] - $today["ordermoney"];
                        $sys_limit = $value["remain_money"] * $getRate;
                        $today_fenhong = $value["remain_fenhong"] * $fhz;
                        if ($today_fenhong > $sys_limit) {
                            $today_fenhong = $sys_limit;
                        }
                        if ($System_FenHong["info"] == 0) {
                            $sys_fenhong = $value["remain_fenhong"] * $System_FenHong["value"];
                            if ($today_fenhong > $sys_fenhong) {
                                $today_fenhong = $sys_fenhong;
                            }
                        }
                        $ss->calc_fenhong($value["uid"], $today_fenhong);
                        \think\Db::name("fenhong_instance")->where(["calctime" => $date])->setDec("sys_fenhong", $today_fenhong);
                        $ss->Instance_log($value["uid"], -$today_fenhong, '系统分红', 'sys');
                    } else {

                        if ($not_today) {
                            $today_fenhong = $value["remain_fenhong"] * $fhz;
                            $sys_limit = $value["remain_money"] * $getRate;
                            if ($today_fenhong > $sys_limit) {
                                $today_fenhong = $sys_limit;
                            }

                            if ($System_FenHong["info"] == 0) {
                                $sys_fenhong = $value["remain_fenhong"] * $System_FenHong["value"];
                                if ($today_fenhong > $sys_fenhong) {
                                    $today_fenhong = $sys_fenhong;
                                }
                            }
                            $ss->calc_fenhong($value["uid"], $today_fenhong);
                            \think\Db::name("fenhong_instance")->where(["calctime" => $date])->setDec("sys_fenhong", $today_fenhong);
                            $ss->Instance_log($value["uid"], -$today_fenhong, '系统分红', 'sys');
                        }
                    }
                }
            }
        }
    }

    public function agentfenhong() {

        if ($this->request->isGet()) {
            $token = $this->request->get('token');
            $ss = new \app\api\controller\wanlshop\Teamwork();
            $list = \think\Db::name("agent_fenhong")->where("remain_fenhong", ">", 0)->select();
            $getRate = \app\api\controller\Tools::getConfig1("max_rate");
            $funds = \app\api\controller\Tools::getConfig1("Agent_Jackpot");
            $date = date('Y-m-d', time());
            $tomorrowyestoday_date = date('Y-m-d', time() - 86400);
            $yestoday = \think\Db::name("agent_fenhong_instance")->where(["calctime" => $tomorrowyestoday_date])->find();
            if ($yestoday) {
                //昨日系统存在分红
                $fenhong = $yestoday["sys_fenhong"];
                $fhz = number_format($funds / $fenhong, 4);
                //系统设定分红值
                $System_FenHong = \think\Db::name("zconfig")->where(["key" => 'System_FenHong'])->find();
                foreach ($list as $key => $value) {
                    $today = \think\Db::name("agent_fenhong_yestoday")->where(["uid" => $value["uid"], 'addtime' => $date])->find();
                    $not_today = \think\Db::name("agent_fenhong_yestoday")->where(["uid" => $value["uid"]])->where("addtime", '<>', $date)->find();
                    if ($today and $value["addtime"] != $date) {
                        $value["remain_fenhong"] = $value["remain_fenhong"] - $today["money"];
                        $value["remain_money"] = $value["remain_money"] - $today["ordermoney"];
                        $sys_limit = $value["remain_money"] * $getRate;
                        $today_fenhong = $value["remain_fenhong"] * $fhz;
                        if ($today_fenhong > $sys_limit) {
                            $today_fenhong = $sys_limit;
                        }
                        if ($System_FenHong["info"] == 0) {
                            $sys_fenhong = $value["remain_fenhong"] * $System_FenHong["value"];
                            if ($today_fenhong > $sys_fenhong) {
                                $today_fenhong = $sys_fenhong;
                            }
                        }
                        // dump($today_fenhong);
                        $ss->calc_fenhong($value["uid"], $today_fenhong, 1);
                        \think\Db::name("agent_fenhong_instance")->where(["calctime" => $date])->setDec("sys_fenhong", $today_fenhong);
                        $ss->Instance_log($value["uid"], -$today_fenhong, '系统分红', 'sys');
                    } else {

                        if ($not_today) {
                            $today_fenhong = $value["remain_fenhong"] * $fhz;
                            $sys_limit = $value["remain_money"] * $getRate;
                            if ($today_fenhong > $sys_limit) {
                                $today_fenhong = $sys_limit;
                            }

                            if ($System_FenHong["info"] == 0) {
                                $sys_fenhong = $value["remain_fenhong"] * $System_FenHong["value"];
                                if ($today_fenhong > $sys_fenhong) {
                                    $today_fenhong = $sys_fenhong;
                                }
                            }
                            //  dump($today_fenhong);
                            $ss->calc_fenhong($value["uid"], $today_fenhong, 1);
                            \think\Db::name("agent_fenhong_instance")->where(["calctime" => $date])->setDec("sys_fenhong", $today_fenhong);
                            $ss->Instance_log($value["uid"], -$today_fenhong, '系统分红', 'sys');
                        }
                    }
                }
            }
        }
    }

    public function InternetAgent() {
        $date_exist = \think\Db::name("date_calc")->where(["calc_date" => date("Y-m-d", time() - 86400)])->find();
        // dump(date("Y-m-d", time() - 86400));
        // die();
        if ($date_exist) {
            $ulist = \think\Db::name("user")->where(["level" => 7])->select();
            if (true) {
                $ex = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 7])->count();
                $ex_six = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 6])->count();
                $ex_serven = 0; //\think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 7])->count();
                if (count($ulist) - $ex + $ex_six > 0) {
                    $money = $date_exist["order_money"] * 0.03 / (count($ulist) - $ex + $ex_six);
                    dump($date_exist["order_money"]);
                    //dump( $date_exist["order_money"]);
                    foreach ($ulist as $key => $value) {
                        $ex = \think\Db::name("new_vip")->where("join_date", '<', date("Y-m-d", time() - 86400))->where(["uid" => $value["id"]])->find();
                        dump($ex);
                        // dump($ex);
                        if (!empty($ex)) {
                            $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                            \app\common\model\User::score($money * 0.1, $value["id"], '网商三级分红_增加10%积分');
                            \app\common\model\User::buycoupon($money * 0.1, $value["id"], '网商三级分红_增加10%积分');
                            $forLog->money($money * 0.8, $value["id"], "网商三级分红", 'sys', 21);
                        }
                    }
                    $six_list = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 6])->select();
                    $serven_list = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 7])->select();
                    foreach ($six_list as $key => $value) {
                        $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                        \app\common\model\User::score($money * 0.1, $value["uid"], '网商一级分红_增加10%积分');
                        \app\common\model\User::buycoupon($money * 0.1, $value["uid"], '网商一级分红_增加10%积分');
                        $forLog->money($money * 0.8, $value["uid"], "网商一级分红", 'sys', 21);
                    }
                }
            }
            $ulist2 = \think\Db::name("user")->where(["level" => 6])->select();
            //dump($ulis)
            if (true) {
                $ex2 = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 6])->count();
                $ex_serven = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 5])->count();
                if (count($ulist2) - $ex2 + $ex_serven > 0) {
                    $money = $date_exist["order_money"] * 0.02 / (count($ulist2) - $ex2 + $ex_serven);
                    dump($ex_serven);
                    foreach ($ulist2 as $key => $value) {
                        $ex = \think\Db::name("new_vip")->where("join_date", '<', date("Y-m-d", time() - 86400))->where(["uid" => $value["id"]])->find();
                        if (!empty($ex)) {
                            $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                            \app\common\model\User::score($money * 0.1, $value["id"], '网商二级分红_增加10%积分');
                            \app\common\model\User::buycoupon($money * 0.1, $value["id"], '网商二级分红_增加10%积分');
                            $forLog->money($money * 0.8, $value["id"], "网商二级分红", 'sys', 21);
                        }
                    }
                    $serven_list = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 5])->select();
                    foreach ($serven_list as $key => $value) {
                        $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();

                        \app\common\model\User::score($money * 0.1, $value["uid"], '网商二级分红_增加10%积分');
                        \app\common\model\User::buycoupon($money * 0.1, $value["uid"], '网商二级分红_增加10%积分');
                        $forLog->money($money * 0.8, $value["uid"], "网商二级分红", 'sys', 21);
                    }
                }
            }
            $ulist3 = \think\Db::name("user")->where(["level" => 5])->select();
            if ($ulist3) {
                $ex3 = \think\Db::name("new_vip")->where("join_date", '=', date("Y-m-d", time() - 86400))->where(["grade" => 5])->count();
                ;
                if ((count($ulist3) - $ex3) > 0) {
                    $money = $date_exist["order_money"] * 0.01 / (count($ulist3) - $ex3);
                    dump(count($ulist3));
                    dump($money);
                    foreach ($ulist3 as $key => $value) {
                        $ex22 = \think\Db::name("new_vip")->where("join_date", '<', date("Y-m-d", time() - 86400))->where(["uid" => $value["id"]])->find();
                        if (!empty($ex22)) {
                            $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                            \app\common\model\User::score($money * 0.1, $value["id"], '网商一级分红_增加10%积分');
                            \app\common\model\User::buycoupon($money * 0.1, $value["id"], '网商一级分红_增加10%积分');
                            $forLog->money($money * 0.8, $value["id"], "网商一级分红", 'sys', 21);
                        }
                    }
                }
            }
        }
    }

    public function share() {







//
//        $s = \think\Db::table("cshopmall_user")->select();
//
//        foreach ($s as $key => $value) {
//            // $this->auth->register(, '123456', '', , ["avatar" => $value["avatar_url"], 'openid' => $value["wechat_open_id"], 'money' => $value["price"]]);
//
//            $ip = request()->ip();
//            $time = time();
//            $uniqid = md5(uniqid(microtime(true), true));
//            $inviteCode = substr(str_shuffle($uniqid), mt_rand(0, strlen($uniqid) - 11), 6);
//            $password = '123456';
//            $extend = [];
//            $data = [
//                'username' => $value["nickname"],
//                'password' => '123456',
//                'email' => '',
//                'avatar' => $value["avatar_url"],
//                'openid' => $value["wechat_open_id"],
//                'money' => $value["price"] * 0.8 ?? 0,
//                'score' => $value["integral"] + $value["price"] * 0.1,
//                'buycoupon' => $value["coupon"] + $value["price"] * 0.1,
//                'mobile' => $value["binding"],
//                'level' => 0,
//                'oldid' => $value["id"],
//                'oldpid' => $value["parent_id"],
//            ];
//            $params = array_merge($data, [
//                'nickname' => preg_match("/^1[3-9]{1}\d{9}$/", $value["nickname"]) ? substr_replace($value["nickname"], '****', 3, 4) : $value["nickname"],
//                'salt' => \fast\Random::alnum(),
//                'jointime' => $time,
//                'joinip' => $ip,
//                'logintime' => $time,
//                'loginip' => $ip,
//                'prevtime' => $time,
//                'status' => 'normal',
//                'recommend' => $inviteCode,
//            ]);
//
//            dump($value["parent_id"]);
//            $params['password'] = $this->auth->getEncryptPassword($password, $params['salt']);
//            $params = array_merge($params, $extend);
//            $user = \app\common\model\User::create($params, true);
//
//            \think\Db::name("user_team")->insert(["uid" => $user->id]);
//        }
//        
//        
        //--------------------------step 2-------------------//
//        $s = \think\Db::table("xsh_user")->select();
//        foreach ($s as $key => $value) {
//            if ($value["oldid"] > 0) {
//                $oInfo = \think\Db::table("xsh_user")->where(["oldid" => $value["oldpid"]])->find();
//                if ($oInfo) {
//                    \think\Db::name("user")->where(["id" => $value["id"]])->update(["pid" => $oInfo["id"]]);
//                    //\think\Db::name("user_team")->whereIn("uid", $value["id"])->setInc("flow", 1);
//                }
//            }
//        }
        //--------------------------step 3------------------//
//        $s = \think\Db::table("xsh_user")->select();
//
//        foreach ($s as $key => $value) {
//            if ($value["pid"] > 0) {
//                $m = $this->returnpids($value["id"]);
//                \think\Db::name("user")->where(["id" => $value["id"]])->update(["pids" => $m]);
//               // $ee = explode(',', $m);
//                // \think\Db::name("user_team")->whereIn("uid", $ee)->setInc("fans", 1);
//                //\think\Db::name("user_team")->whereIn("uid", $ee)->setInc("flow", 1);
//            }
//        }
        //--------------------------step 4------------------//
//        $s = \think\Db::table("xsh_user")->select();
//        foreach ($s as $key => $value) {
//            if ($value["pid"] != 0) {
//                $m = $value["pids"];
//                $ee = explode(',', $m);
//                \think\Db::name("user_team")->whereIn("uid", $ee)->setInc("fans", 1);
//                \think\Db::name("user_team")->whereIn("uid", $ee)->setInc("flow", 1);
//            }
//        }
//           //--------------------------step 5------------------//
        //   $common_count = Order::find()->where(['user_id' => $v['id'], 'is_pay' => 1, 'is_delete' => 0])->count();
//        $s = \think\Db::table("xsh_user")->select();
//        foreach ($s as $key => $value) {
//            $s = \think\Db::table("cshopmall_order")->where(['user_id' => $value['oldid'], 'is_pay' => 1, 'is_delete' => 0])->find();
//            if ($s) {
//                $m = $value["pids"];
//                $ee = explode(',', $m);
//                \think\Db::name("user")->where(["id" => $value["id"]])->update(["level" => 1]);
//                \think\Db::name("user_team")->whereIn("uid", $ee)->setInc("vip", 1);
//                \think\Db::name("user_team")->whereIn("uid", $ee)->setDec("fans", 1);
//            }
//        }
//        
//        
//        
//        require __DIR__ . '../../../../vendor/qrcode.php';
//        $uinfo = \app\common\model\User::get($this->request->post('uid'));
//
//        $value = 'https://m.xzsc.hxzcweb.com/#/pages/user/auth/register?url=&invite=' . $uinfo["recommend"];         //二维码内容
//        $errorCorrectionLevel = 'L';    //容错级别
//        $matrixPointSize = 7;            //生成图片大小
//        //生成二维码图片
//        $filename = __DIR__ . '/../../../public/';
//        $filename2 = 'assets/addons/wanlshop/img/qrcode/share-' . $uinfo["id"] . '.png';
//        $filename .= $filename2;
//        if (!file_exists($filename) || 1) {
//            \QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
//
//            $img1 = imagecreatefromjpeg(__DIR__ . '/../../../public/assets/addons/wanlshop/img/qrcode/cover.png');
//            dump($img1);
//            $img2 = imagecreatefrompng(__DIR__ . '/../../../public/' . $filename2);
//            imagecopyresampled($img1, $img2, 205, 430, 0, 0, 350, 350, 235, 235);
//            imagepng($img1, $filename);
//        }
//        $data = [
//            'link' => $value,
//            'img' => "https://" . $_SERVER['HTTP_HOST'] . "/{$filename2}",
//        ];
    }

    public function changeTeam() {

        $ulist = \think\Db::name("user")->where("id", ">", 143397)->select();
        foreach ($ulist as $key => $value) {
            if ($value["level"] > 0) {
                $m = $value["pids"];
                $ee = explode(',', $m);
                \think\Db::name("new_vip")->insert(["uid" => $value["id"], "join_date" => '2021-05-15', "grade" => $value["level"]]);
                \think\Db::name("user_team")->whereIn("uid", $ee)->setInc("vip", 1);
                \think\Db::name("user_team")->whereIn("uid", $ee)->setDec("fans", 1);
            }
        }
    }

    public function returnpids($uid) {

        $ids = "";
        while (true) {
            $pinfo = \app\common\model\User::get($uid);
            if ($uid != $pinfo["pid"]) {
                $ids .= $pinfo["pid"] . ",";
                $uid = $pinfo["pid"];
            } else {
                break;
            }
        }
        return $ids;
    }

}

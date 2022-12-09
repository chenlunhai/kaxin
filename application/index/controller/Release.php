<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\index\controller;

use app\common\controller\Frontend;
use app\api\model\wanlshop\Teamwork;
use think\Db;
use fast\Random;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Exception\QrCodeException;

class Release extends Frontend {

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    private static $jsRate = [];
    private static $shRate = [];
    protected $_qr;
    protected $_encoding = 'UTF-8';              // 编码类型
    protected $_size = 300;                  // 二维码大小
    protected $_logo = true;                // 是否需要带logo的二维码
    protected $_logo_url = '/assets/addons/wanlshop/img/qrcode/qrcode.png';                   // logo图片路径
    protected $_logo_size = 1200;                   // logo大小
    protected $_title = false;                // 是否需要二维码title
    protected $_title_content = '';                   // title内容
    protected $_generate = 'display';            // display-直接显示  writefile-写入文件
    protected $_file_name = './';                 // 写入文件路径

    public function __construct() {
        
    }

    public function changeUinfo() {
        $allcids = $this->getAllNextId(2791);
        foreach ($allcids as $key => $value) {
            $uinfo = Db::name("user")->where(["id" => $value])->find();
            $pids = "2791,2788,2783,2776,2770,2768,2764,2761,2758,2755,2752,2751,2750,2749,2748,2705,2678,2657,2654,2651,2650,2649,2595,0";
            if ($uinfo["pid"] == 2791) {
                Db::name("user")->where(["id" => $value])->update(["pid" => 2857]);
            }
            $sa = str_replace($pids, "2857,2856,2855,2751,2750,2749,2748,2705,2678,2657,2654,2651,2650,2649,2595,0", $uinfo["pids"]);
            Db::name("user")->where(["id" => $value])->update(["pids" => $sa]);
        }
           
        $allcids2 = $this->getAllNextId(2798);
        foreach ($allcids2 as $key => $value) {
            $uinfo = Db::name("user")->where(["id" => $value])->find();
           // dump($uinfo["pids"]);
            $pids = "2798,2779,2771,2768,2764,2761,2758,2755,2752,2751,2750,2749,2748,2705,2678,2657,2654,2651,2650,2649,2595,0";
            if ($uinfo["pid"] == 2798) {
                Db::name("user")->where(["id" => $value])->update(["pid" => 2858]);
            }
            $sa = str_replace($pids, "2858,2856,2855,2751,2750,2749,2748,2705,2678,2657,2654,2651,2650,2649,2595,0", $uinfo["pids"]);
            Db::name("user")->where(["id" => $value])->update(["pids" => $sa]);
        }

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

    public function s() {
        $List = Db::name("c_new_static_instance")->where(["status" => 1, 'uid' => 2572])->select();
        $conf = Db::name("c_rate_config")->where(["id" => 1])->find();
        $StaticRate = $conf["staticrate"];
        foreach ($List as $key => $value) {
            $user = \app\common\model\User::get($value["uid"]);
            if ($user["prate"] != 0) {
                $getBalance = round($value["remain_num"] * $user["prate"] / 100, 2);
            } else {
                $getBalance = round($value["remain_num"] * $StaticRate / 100, 2);
            }
            $old = Db::name("c_static_instance")->where(["status" => 1, "uid" => $value["uid"]])->find();
            if (null !== $user) {
                if ($user["status"] == "normal" and $getBalance > 0) {

                    $update_Static_Instance = ["remain_num" => Db::raw("remain_num-" . $getBalance),
                        "was_num" => Db::raw("was_num-" . $getBalance),
                        "update_time" => time(),
                    ];
                    if ($old and $old["remain_num"] > 0) {
                        Db::name("c_static_instance")->where(["id" => $value["id"]])->update($update_Static_Instance);
                    } else {
                        Db::name("c_new_static_instance")->where(["id" => $value["id"]])->update($update_Static_Instance);
                    }
                }
                if ($getBalance > 0) {
                    $ss = \app\common\model\ScoreLog::create(['user_id' => $value["uid"], 'score' => -$getBalance, 'before' => $value['remain_num'], 'after' => $value['remain_num'] - $getBalance, 'memo' => '静态释放积分', "source" => 99]);
                    if ($old and $old["remain_num"] > 0) {
                        $user->setDec("score", $getBalance);
                    } else {
                        $user->setDec("integral", $getBalance);
                        $user->setDec("total_integral", $getBalance);
                    }

                    $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                    if ($old and $old["remain_num"] > 0) {
                        $forLog->money($getBalance * 0.9, $value["uid"], '静态释放' . $value["id"], 'sys', 'Si' . time());
                    } else {
                        $forLog->balance($getBalance * 0.9, $value["uid"], '静态释放' . $value["id"], 'sys', 'Si' . time());
                    }
                    $forLog->usecoupon($getBalance * 0.1, $value["uid"], "静态释放", 'sys', 'Si' . time());
//分享奖励
                    self::TeamBonus($user, $conf["sharerate"], $getBalance);
                    self::Recommend($user, $conf["recommendrate"], $getBalance);
                }
            } else {
                
            }
//推荐奖
        }
    }

    private function Recommend($user, $rateStr, $money) {
        $rate = explode(',', $rateStr);
        $conf = Db::name("c_rate_config")->where(["id" => 1])->find();

        $a = 0;
        foreach ($rate as $k => $v) {
            $condition = explode('-', $v);
            self::$jsRate[$a++] = [
                "level" => $condition[0],
                "need" => $condition[1],
                'rate' => $condition[2],
            ];
        }
        $pList = explode(',', $user["pids"]);
        krsort($pList);
// dump($conf);
//dump($pList);
        $b = 0;
        $m = 1;
        //dump($pList);
        foreach ($pList as $key => $value) {
            $pinfo = \app\common\model\User::get($value);
//推荐奖励：直推1人，享受1代内静态释放的25%加速；直推2人，2代的8%；直推3人，3代的15%；直推4人，10代的2%
            if ($pinfo) {
//dump(count(self::$jsRate));
                if ($b <= count(self::$jsRate) - 1) {
//$b = count(self::$jsRate) - 1;
                    $child_num = self::$jsRate[$b];
//dump($b);
                } else {
                    $s = count(self::$jsRate) - 1;
                    $child_num = self::$jsRate[$s];
                    $b = $s;
                }

                //dump($child_num);
                if ($pinfo["status"] == "normal") {
                    $ChildNum = Db::name("c_team_total")->where(["pid" => $value])->where("my_total", ">", 0)->count();
                    if ($pinfo["id"] == 2562) {
                        echo 777;
                        dump($ChildNum);
                    }
                    if ($ChildNum >= self::$jsRate[$b]["need"]) {
//释放加速

                        $getBalance = self::$jsRate[$b]["rate"] * $money / 100;

                        if ($pinfo["integral"] - $getBalance < 0) {
                            $getBalance = $pinfo["integral"];
                        }
                        if ($getBalance <= 0) {
                            continue;
                        }
                        if ($m <= 10) {
                            $str = '推荐奖励(加速)' . $user["id"] . "/" . $m . "级";
                            self::bonus($pinfo, $getBalance, $str, 99);
                            self::TeamBonus($pinfo, $conf["sharerate"], $getBalance);
                        }
                    }
                }
                $b++;
                $m++;
            }
        }
    }

    /** 1-5,2-8,3-10,4-12,5-15
     * 分享奖励：级差制。V1享受团队释放总量的5%加速，V2享受8%，V3享受10%，V4享受12%，V5享受15% 
     *     
     *  */
    private function TeamBonus($user, $rateStr, $money) {
        $rate = explode(',', $rateStr);
        $a = 0;
        foreach ($rate as $k => $v) {
            $condition = explode('-', $v);
            self::$shRate[$a++] = [
                "level" => $condition[0],
                'rate' => $condition[1],
            ];
        }
        $user = \app\common\model\User::get($user["id"]);
        $pList = explode(',', $user["pids"]);
// krsort($pList);
        $pList = array_reverse($pList);
        $temp = 0;
//dump($pList);
        foreach ($pList as $key => $value) {
            $pInfo = \app\common\model\User::get($value);
            var_dump($pInfo["id"]);
            if ($pInfo["level"] != 0) {
                $level = $pInfo["level"];
                $rate = self::$shRate[$level - 1]["rate"];
                if ($temp > 0 and $level == $temp) {
                    $rate = $getBalance * 5 / 100;
                }
                if ($level > $temp) {
                    if ($temp - 1 >= 0) {
                        $rate = $rate - self::$shRate[$temp - 1]["rate"];
                    }
                    $temp = $level;
                } else {
                    $rate = 0;
                }
                $getBalance = $rate * $money / 100;
                if ($getBalance > 0) {
                    if ($pInfo["integral"] - $getBalance < 0) {
                        $getBalance = $pInfo["integral"];
                    }
                    if ($getBalance <= 0) {
                        continue;
                    }

                    self::bonus($pInfo, $getBalance, '分享奖励(加速)' . $user["id"], 99);
                }
            }
        }
    }

    public function bonus($pinfo, $getBalance, $meno = "静态释放", $souce = 99) {
// return;
        $uid = $pinfo["id"];
        $update_Static_Instance = ["remain_num" => Db::raw("remain_num-" . $getBalance),
            "was_num" => Db::raw("was_num+" . $getBalance),
            "update_time" => time(),
        ];
        $old = Db::name("c_static_instance")->where(["status" => 1, "uid" => $uid])->find();

        $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();

        if ($old and $old["remain_num"] > 0) {
            $stinfo = Db::name("c_static_instance")->where(["uid" => $uid])->find();
            Db::name("c_static_instance")->where(["uid" => $uid])->update($update_Static_Instance);
            $forLog->money($getBalance, $uid, $meno, 'sys', 'Si' . time());
        } else {
            $stinfo = Db::name("c_new_static_instance")->where(["uid" => $uid])->find();
            if ($stinfo["remain_num"] > 0) {
                Db::name("c_new_static_instance")->where(["uid" => $uid])->update($update_Static_Instance);
                $forLog->balance($getBalance * 0.9, $uid, $meno, 'sys', 'Si' . time());
                $forLog->usecoupon($getBalance * 0.1, $uid, $meno, 'sys', 'Si' . time());
            }
        }
        if ($stinfo["remain_num"] > 0) {
            \app\common\model\ScoreLog::create(['user_id' => $uid, 'score' => -$getBalance, 'before' => $stinfo['remain_num'], 'after' => $stinfo['remain_num'] - $getBalance, 'memo' => $meno, "source" => $souce]);
            if ($old and $old["remain_num"] > 0) {
                $pinfo->setDec("score", $getBalance);
            } else {
                $pinfo->setDec("integral", $getBalance);
                $pinfo->setDec("total_integral", $getBalance);
            }
        }
    }

    private function agentslow($uid, $fromuid, $oid, $money) {
        Db::name("agent_speed")->insert(
                [
                    "uid" => $uid,
                    "fromuid" => $fromuid,
                    "oid" => $oid,
                    "money" => $money,
                    "flag" => 0,
                    "addtime" => time(),
                ]
        );
    }

    public function Testteam() {

        //\app\api\controller\wanlshop\Teamwork::updateUserLevel0();

        $list = Db::name("user")->select();
        foreach ($list as $key => $value) {
            $team = Db::name("c_team_total")->where(["uid" => $value["id"]])->find();
            $istd = Db::name("c_new_static_instance")->where(["uid" => $value["id"]])->find();
            if ($team["pid"] != $value["pid"]) {
                Db::name("c_team_total")->where(["id" => $team["id"]])->update(["pid" => $value["pid"]]);
            }
            if ($istd["in_num"] > 0 and $team["my_total"] != $istd["in_num"]) {
                $ids = explode(',', $value["pids"]);
                db("c_team_total")->where(["uid" => $value["id"]])->update(["my_total" => Db::raw("my_total+" . $istd["in_num"]), "total" => Db::raw("total+" . $istd["in_num"])]);
                db("c_team_total")->where("uid", 'in', $ids)->update(["team_total" => Db::raw("team_total+" . $istd["in_num"]), "total" => Db::raw("total+" . $istd["in_num"])]);
            }
        }

//        $pay = model('app\api\model\wanlshop\Pay')
//                ->where('order_id', $oid)
//                ->where('user_id', $this->auth->id)
//                ->find();
//        $pids = "0,1,";
//        $uid = 2;
//        $ids = explode(',', $pids);
//        $exist = db("c_team_total")->where(["uid" => $uid])->find();
//        $money = 112;
//        if (!$exist) {
//            Db::name("c_team_total")->insert(["uid" => 1, "my_total" => $money, "total" => $money, "team_total" => 0]);
//        } else {
//            db("c_team_total")->where(["uid" => $uid])->update(["my_total" => Db::raw("my_total+" . $money), "total" => Db::raw("total+" . $money)]);
//            db("c_team_total")->where("uid", 'in', $ids)->update(["team_total" => Db::raw("team_total+" . $money), "total" => Db::raw("total+" . $money)]);
//        }
//        var_dump($exist);
// Db::name("c_teamwork")->
///$update = Teamwork::save(["total" => ["exp", "total+" . $pay["total_amount"]], "my_total" => ["exp", "my_total+" . $pay["total_amount"]]], ["uid" => 1]);
//\app\api\model\wanlshop\Teamwork::update(["team_total" => ["exp", "team_total+" . $pay["total_amount"]]], ["uid" => $ids]);
    }

    public function t() {

        $ulist = Db::table("xsh_user")->select();
        $auth = new \app\common\library\Auth();
        foreach ($ulist as $key => $value) {
            \app\common\model\ScoreLog::create(['user_id' => $value["id"], 'score' => $getBalance, 'before' => $value['score'], 'after' => $stinfo['score'], 'memo' => '系统导入', "source" => 0]);
            $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
            $forLog->money($value['money'], $value["id"], "系统导入", 'sys', 'Si' . time());

///===========step 5
// $LevelList = Db::table("zjhj_bd_user")->where(["id" => $value["nickname"]])->find();
// if ($LevelList) {
//     //if ($LevelList["member_level"] > 0) {
//     Db::name("user")->where(["id" => $value["id"]])->update(["nickname" => $LevelList["nickname"], "username" => $LevelList["username"]]);
//     //}
// }
// ///===========end  step 5
// ///===========step 4
// $LevelList = Db::table("zjhj_bd_user_identity")->where(["user_id" => $value["nickname"]])->find();
// if ($LevelList) {
//     if ($LevelList["member_level"] > 0) {
//         Db::name("user")->where(["id" => $value["id"]])->update(["level" => $LevelList["member_level"]]);
//     }
// }
///===========end  step 4
///===========step 3
//             $ids = $this->fabolic($value["id"]);
//             $str = array_reverse($ids);
//             $str = implode(',', $str) . ",";
//             Db::name("user")->where(["id" => $value["id"]])->update(["pids" => $str]);
//             //      $xsh_uinfo = Db::name("zjhj_bd_user")->alias('a')->join("zjhj_bd_user_info b", "a.id=b.user_id")->where(["a.mobile" => $value["mobile"]])->find();
//             $TeamInfo = Db::table("team_total")->where(["user_id" => $value["nickname"]])->find();
//             if ($TeamInfo) {
//                 Db::name("c_team_total")->insert(
//                         [
//                             "uid" => $value["id"],
//                             "pid" => $value["pid"],
//                             "total" => $TeamInfo["total"],
//                             "team_total" => $TeamInfo["team_total"],
//                             "my_total" => $TeamInfo["my_total"],
//                         ]
//                 );
//             } else {
// //                
//                   Db::name("c_team_total")->insert(
//                         [
//                             "uid" => $value["id"],
//                             "pid" => $value["pid"],
//                             "total" => 0,
//                             "team_total" => 0,
//                             "my_total" => 0,
//                         ]
//                 );
//                 echo $value["id"] . "_";
//             }
//             ////==========end step 3         
//             $StaticInfo = Db::table("static_instance")->where(["user_id" => $value["nickname"]])->select();
//             if ($StaticInfo) {
//                 $in_num = $total_num = $remain_num = $was_num = 0;
//                 foreach ($StaticInfo as $k => $v) {
//                     $in_num += $v["in_num"];
//                     $total_num += $v["total_num"];
//                     $remain_num += $v["remain_num"];
//                     $was_num += $v["was_num"];
//                 }
//                 Db::name("c_static_instance")->insert(
//                         ["uid" => $value["id"],
//                             "in_num" => $in_num,
//                             "total_num" => $total_num,
//                             "remain_num" => $remain_num,
//                             "was_num" => $was_num,
//                             "create_time" => time(),
//                             "commodity_id" => 0
//                         ]
//                 );
//             }
//            
//            
//            
//            
//   var_dump($str); =================第二步开始
// $xsh_uinfo = Db::name("zjhj_bd_user")->alias('a')->join("zjhj_bd_user_info b", "a.id=b.user_id")->where(["a.mobile" => $value["mobile"]])->find();
// $ss = Db::name("zjhj_bd_user")->where(["id" => $xsh_uinfo["parent_id"]])->find();
// if ($ss) {
//     $mobile = $ss["mobile"];
//     $ssd = Db::name("user")->where(["mobile" => $mobile])->find();
//     if ($ssd) {
//         Db::name("user")->where(["mobile" => $value["mobile"]])->update(["pid" => $ssd["id"], "is_blacklist" => $xsh_uinfo["is_blacklist"]]);
//     }
// } else {
//     echo $value["id"] . "_";
// }
// =========第二步结束
// 
// ============第一步开始
// $uinfo = Db::table("xsh_zjhj_bd_user_info")->where(["user_id" => $value["id"]])->find(); //获取pids--插入team
// $balanceinfo = Db::table("balance")->where(["user_id" => $value["id"], "balance_type" => 0])->find();
// $ScoreInfo = Db::table("balance")->where(["user_id" => $value["id"], "balance_type" => 5])->find();
// $auth->Temporaryregister($value["id"], '123456', '', $value["mobile"], null, $uinfo["path_ids"], $balanceinfo["balance"], $ScoreInfo["balance"]);
        }



//   $this->agent(10, 5, "Q-L-3,C-W-5,P-H-7", 100, 20);
//V2：不同部门产生2个V1
//V3：不同部门产生3个V2
//V4：不同部门产生2个V3
//V5：不同部门产生3个V4
//        $GoodsInfo = \app\index\model\wanlshop\OrderGoods::get(["order_id" => 10]);
//        $GoodsCatInfo = \app\api\model\wanlshop\Goods::get($GoodsInfo["goods_id"]);
//        var_dump($GoodsInfo["goods_sku_id"]);
//        $integral = \app\index\model\wanlshop\GoodsSku::get($GoodsInfo["goods_sku_id"])["integral"];
//        var_dump($integral);
//             $GoodsInfo=\app\index\model\wanlshop\OrderGoods::get(["order_id"=>5]);
//             $GoodsCatInfo=\app\api\model\wanlshop\Goods::get($GoodsInfo["goods_id"]);
//             dump($GoodsCatInfo["shop_category_id"]);
//           $pids= explode(',', '0,');
//           
//           echo $pids[count($pids)-2];
//        echo "hello,world@";
    }

    public function fabolic($uid, &$ids = array()) {
// static $ids = "";
// var_dump($uid); 
        if ($uid > 0) {
            $uinfo = Db::name("user")->where(["id" => $uid])->find();
            $ids[] = $uinfo["pid"];
            $uid = $uinfo["pid"];
            self::fabolic($uid, $ids);
        }
        return $ids;
    }

}

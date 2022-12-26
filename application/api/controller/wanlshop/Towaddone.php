<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\api\controller\wanlshop;

use think\Db;

/**
 * Description of Towaddone
 *
 * @author Cc
 */
class Towaddone {

    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function jdb($pid) {
        $payInfo = \app\index\model\wanlshop\Pay::get($pid);
        $order = \app\api\model\wanlshop\Order::get($payInfo["order_id"]);
        $fundsInfo = Db::name("wanlshop_order_goods")->where(["order_id" => $payInfo["order_id"]])->find();
        $skuInfo = Db::name("wanlshop_goods_sku")->where(["id" => $fundsInfo["goods_sku_id"]])->find();
        $goodsInfo = Db::name("wanlshop_goods")->where(["id" => $fundsInfo["goods_id"]])->find();

        if ($goodsInfo["category_id"] != 105 and $goodsInfo["category_id"] != 104) {
            if ($skuInfo["integral"] > 0) {
                $score = $fundsInfo["number"] * $skuInfo["integral"];
// Db::name("user")->where(["id" => $payInfo["user_id"]])->setInc("balance", $skuInfo["integral"]);
                \addons\wanlshop\library\WanlPay\WanlPay::balance($fundsInfo["number"] * $skuInfo["integral"], $payInfo["user_id"], "购物赠送", "withdraw", $payInfo["order_id"]);
                Db::table("static_instance")->insert(["user_id" => $payInfo["user_id"], "in_num" => $score, "total_num" => $score, "remain_num" => $score, "create_time" => time()]);
            }
            $uInfo = Db::name("user")->where(["id" => $payInfo["user_id"]])->find();
            $pids = $uInfo["pids"];
            $pInfo = explode(',', $pids);
            Db::name("user_team")->whereIn("uid", $pInfo)->setInc("teamorder", $payInfo["order_price"]);
            Db::name("user_team")->whereIn("uid", $pInfo)->setInc("todayorder", $payInfo["order_price"]);
            Db::name("user_team")->where(["uid" => $payInfo["user_id"]])->setInc("own_money", $payInfo["order_price"]);
            if ($payInfo["order_price"] == 3000) {
                Db::name("user_team")->whereIn("uid", $pInfo)->setInc("g1", 1);
            } else if ($payInfo["order_price"] == 5000) {
                Db::name("user_team")->whereIn("uid", $pInfo)->setInc("g2", 1);
            } else
            if ($payInfo["order_price"] == 15000) {
                Db::name("user_team")->whereIn("uid", $pInfo)->setInc("g3", 1);
            } else if ($payInfo["order_price"] == 30000) {
                Db::name("user_team")->whereIn("uid", $pInfo)->setInc("g4", 1);
            } else {
                
            }

            $sonlist = Db::name("user")->where(["pid" => $uInfo["pid"]])->select();
            if (count($sonlist) >= 3) {
                $hadcount = 0;
                foreach ($sonlist as $key => $value) {
                    $hasOrder = Db::name("user_team")->where(["uid" => $value["id"]])->find();
                    if ($hasOrder["own_money"] >= 3000) {
                        $hadcount += 1;
                    }
                }
                if ($hadcount >= 3) {
                    $exit = Db::name("user_loop")->where(["uid" => $uInfo["pid"]])->find();
                    if (!$exit) {
                        Db::name("user_loop")->insert(["uid" => $uInfo["pid"]]);
                    }
                }
            }
            $sonlist2 = Db::name("user")->where(["pid" => $payInfo["user_id"]])->select();
            if (count($sonlist2) >= 3) {
                $hadcount1 = 0;
                foreach ($sonlist2 as $key => $value) {
                    $hasOrder1 = Db::name("user_team")->where(["uid" => $value["id"]])->find();
                    if ($hasOrder1["own_money"] >= 3000) {
                        $hadcount1 += 1;
                    }
                }
                if ($hadcount1 >= 3) {
                    $exit = Db::name("user_loop")->where(["uid" => $payInfo["user_id"]])->find();
                    if (!$exit) {
                        Db::name("user_loop")->insert(["uid" => $payInfo["user_id"]]);
                    }
                }
            }


            $this->UserLevel($uInfo["id"] . "," . $uInfo["pids"]);
        } else {
//            if ($goodsInfo["category_id"] == 104) {
//                $needscore = $fundsInfo["number"] * $skuInfo["integral"];
//                self::score($payInfo["user_id"], -$needscore, "消费区扣除", 7, $payInfo["order_id"]);
//            }
        }
    }

    public static function SnActive($uid, $sn, $bid) {
        $archiveMoney = \app\api\controller\Tools::getConfig1("archivemoney");
        $uInfo = Db::name("user")->where(["id" => $uid])->find();
        $snInfo = Db::name("kaxin_user_sn")->where(["snid" => $sn])->find();
        $OwnInfo = Db::name("user_team")->where(["uid" => $uid])->find();
        $bFunds = Db::name("kaxin_brandtax")->where(["bid" => $bid])->find();
        $funds_array = explode(",", $bFunds["money"]);
        if ($OwnInfo["own_money"] >= $funds_array[0] and $snInfo["status"] == 0) {
             self::bonus($uid, $funds_array[0], "用户激活奖励", '99_'.$sn);
            Db::name("kaxin_user_sn")->where(["snid" => $sn])->update(["status" => '1', 'udpatetime' => time()]);
        }
    }

    public static function AddTeamInfo($sn, $money) {
        $snInfo = Db::name("kaxin_user_sn")->where(["snid" => $sn, "belongsto" => 0])->find();
        if ($snInfo) {
            $uInfo = Db::name("user")->where(["id" => $snInfo["uid"]])->find();
            $pInfo = explode(',', $uInfo["pids"]);
            Db::name("user_team")->whereIn("uid", $pInfo)->setInc("teamorder", $money);
            Db::name("user_team")->whereIn("uid", $pInfo)->setInc("todayorder", $money);
            Db::name("user_team")->where(["uid" => $snInfo["uid"]])->setInc("own_money", $money);
            $posInfo = Db::name("kaxin_pos")->where(["possn" => $sn])->find();
            self::SnActive($snInfo["uid"], $sn, $posInfo["bid"]);
            self::KaxinUserLevel($uInfo["pids"]);
            dump($uInfo["pids"]);
//            dump($money);
//            dump($posInfo["bid"]);
//            dump($snInfo["uid"]);
            self::KaxinTax($uInfo["pids"], $money, $posInfo["bid"], $snInfo["uid"]);
        }
    }

    public static function KaxinUserLevel($pids) {
        $pInfo = explode(',', $pids);
        $config = Db::name("kaxin_config_tax")->where("1=1")->select();
        foreach ($pInfo as $key => $value) {
            $temp = $u_level = 0;
            $uInfo = Db::name("user")->where(["id" => $value])->find();
            $U_teamInfo = Db::name("user_team")->where(["uid" => $value])->find();
            $U_info = Db::name("user")->where(["pid" => $value])->count();
            $U_level = Db::name("user")->where(["pid" => $value, "level" => $uInfo["level"]])->count();
            foreach ($config as $k => $v) {
                if ($U_teamInfo["teamorder"] >= $v["money"] and $U_info >= $v["personcount"] and $U_level >= $v["soncount"]) {
                    $temp = $v["id"];
                }
            }
            if ($temp > 0 and $temp > $uInfo["level"]) {
                Db::name("user")->where(["id" => $value])->update(["level" => $temp]);
            }
        }
    }

    public static function KaxinTax($pids, $money, $brandType, $from) {
        $pInfo = explode(',', $pids);
        $bFunds = Db::name("kaxin_brandtax")->where(["bid" => $brandType])->find();
        $funds_array = explode(",", $bFunds["tax"]);
        $temp = 0;
        foreach ($pInfo as $key => $value) {
            $uInfo = Db::name("user")->where(["id" => $value])->find();
            if ($uInfo["level"] > 0 and $uInfo["level"] > $temp) {
                if ($temp > 0) {
                    $tax = $funds_array[$uInfo["level"] - 1] - $funds_array[$temp - 1];
                } else {
                    $tax = $funds_array[$uInfo["level"] - 1];
                }
                if ($tax > 0) {
                    $tax = $tax / 10000;
                    self::bonus($value, $tax * $money, "L" . $uInfo["level"] . "级差奖励", $from);
                    $temp = $uInfo["level"];
                }
            }
        }
    }

    //成交之后调用这个函数
    public function dirctPush($uid) {
        $u = Db::name("user")->where(["id" => $uid])->find();
        $exist = Db::name("user_relationship")->where(["uid" => $uid])->find();
        $ex = explode(',', $u["pids"]);
        $a = 0;
        foreach ($ex as $key => $value) {
            if ($value != 0) {
                $zt_num = Db::name("user_relationship")->where(["uid" => $value])->find();
                $up = Db::name("user")->where(["id" => $value])->find();
                if ($zt_num["vip"] == 0) {
                    \app\common\model\User::money(100, $value, '直推奖励');
                    $jt_num = Db::name("user_relationship")->where(["uid" => $up["pid"]])->find();
                    if ($jt_num["vip"] == 1) {
                        \app\common\model\User::money(200, $zt_num["pid"], '间推奖励');
                    }
                    $sonlist = Db::name("user")->where(["pid" => $value])->select();
                    $counta = 0;
                    $ids = "";
                    foreach ($sonlist as $k => $v) {
                        $hasInfo = Db::name("user_relationship")->where(["uid" => $v])->find();
                        if ($hasInfo) {
                            $counta += 1;
                            $ids += $v . ",";
                        }
                    }
                    if ($counta == 2) {
                        Db::name("user_relationship")->where(["uid" => $value])->update(["vip" => 1, "pid" => 0]);
                        $ids_array = explode(",", $ids);
                    }
                }
                if ($pinfo["vip"] == 1 and $a == 0) {
                    \app\common\model\User::money(300, $value, '直推奖励');
                }
                if ($pinfo["vip"] == 1 and $a == 1) {
                    \app\common\model\User::money(200, $value, '直推奖励');
                }
                $a++;
            }
        }
        if (!$exist) {
            $pinfo = Db::name("user_relationship")->where(["uid" => $u["pid"]])->find();
            if ($pinfo and $pinfo["vip"] == 0) {
                $sonlist = Db::name("user")->where(["pid" => $u["pid"]])->select();
                $counta = 0;
                foreach ($sonlist as $key => $value) {
                    $hasInfo = Db::name("user_relationship")->where(["uid" => $value])->find();
                    if ($hasInfo) {
                        $counta += 1;
                    }
                }
                if ($counta == 2) {
                    Db::name("user_relationship")->where(["uid" => $u["pid"]])->update(["pid" => 0, "vip" => 1]);
                    $ex = explode(',', $pinfo["pids"]);
                    $ppi = 0;
                    foreach ($ex as $key => $value) {
                        $exist = Db::name("user_relationship")->where(["uid" => $value])->find();
                        if ($exist) {
                            if ($exist["vip"] == 1) {
                                $ppi = $exist["id"];
                            }
                        }
                    }
                    Db::name("user_relationship")->insert(["uid" => $uid, "pid" => $ppi]);
                }
            }
        }
    }

    public static function score($user_id, $money, $memo, $type = '', $ids = '') {
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
                        'service_ids' => $ids // 业务ID
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更积分失败'];
        }
    }

    public function dateCalc($money) {
        $exist = Db::name("date_calc")->where(["calc_date" => date('Y-m-d', time())])->find();
        if ($exist) {
            Db::name("date_calc")->where(["id" => $exist["id"]])->setInc("order_money", $money);
        } else {
            Db::name("date_calc")->insert(["calc_date" => date('Y-m-d', time()), 'order_money' => $money]);
        }
    }

    public function UserLevelAgain($uid) {
        $uInfo = Db::name("user_team")->where(["uid" => $uid])->find();
        $u = Db::name("user")->where(["id" => $uid])->find();
        $ex = explode(',', $u["pids"]);
        foreach ($ex as $key => $value) {
            if ($value != 0) {
                $zt_num = Db::name("user")->where(["pid" => $value])->where("level", '>', 0)->count();
                $up = Db::name("user")->where(["id" => $value])->find();
                if ($up["level"] > 0) {
                    if ($up["level"] < 5 or $up["level"] == 0) {
                        $user_level = 4;
                    } else {
                        $user_level = $up["level"];
                    }
                    $this->calc_user_level($value, $zt_num, $user_level);
                }
            }
        }
    }

    public function calc_user_level($uid, $zt_num, $ulevel) {
        $son_info = Db::name("user")->where(["pid" => $uid])->select();
        $max = $toal = 0;
        foreach ($son_info as $key => $value) {
            $s = Db::name("user_team")->where(["uid" => $value["id"]])->find();
            if ($s["order_money"] + $s["own_money"] >= $max) {
                $max = $s["order_money"] + $s["own_money"];
            }
            $toal += $s["order_money"] + $s["own_money"];
        }
        $flag = false;
        $grade = 0;
        if ($ulevel == 4) {
            if ($zt_num >= 10) {
                if ($max >= 400000) {
                    if ($toal - $max >= 100000) {
                        Db::name("user")->where(["id" => $uid])->update(["level" => 7]);
                        $grade = 7;
                        $flag = true;
                    }
                }
            }
        }
        if ($ulevel == 7) {
            if ($zt_num >= 30) {
                if ($max >= 1000000) {
                    if ($toal - $max >= 300000) {
                        Db::name("user")->where(["id" => $uid])->update(["level" => 6]);
                        $grade = 6;
                        $flag = true;
                    }
                }
            }
        }

        if ($ulevel == 6) {
            if ($zt_num >= 50) {
                if ($max >= 5000000) {
                    if ($toal - $max >= 1000000) {
                        Db::name("user")->where(["id" => $uid])->update(["level" => 5]);
                        $grade = 5;
                        $flag = true;
                    }
                }
            }
        }
        if ($flag) {
            Db::name("new_vip")->where(["uid" => $uid])->delete();
            Db::name("new_vip")->insert(["uid" => $uid, "join_date" => date("Y-m-d", time()), "grade" => $grade]);
        }
    }

    public function UserLevel($pdiss) {
        $pids = explode(",", $pdiss);
        foreach ($pids as $key => $value) {
            $uinfo = Db::name("user")->where(["id" => $value])->find();
            $sonlist = Db::name("user_team")->alias("a")->join("xsh_user b", "a.uid=b.id")->where("b.pid", $value)->field("a.*,b.level")->order("a.teamorder desc")->select();
            if (count($sonlist) >= 3) {
                if ($sonlist[2]["teamorder"] + $sonlist[2]["own_money"] >= 60000 and $uinfo["level"] == 0) {
                    Db::name("user")->where(["id" => $value])->update(["level" => 1]);
// dump($value);
                    $this->parentslevel($value, 1);
                    $this->uselfLevel($value, 1);
                } elseif ($sonlist[2]["teamorder"] + $sonlist[2]["own_money"] >= 60000 and $uinfo["level"] > 0) {
                    $sonLevelCount = Db::name("user_team")->alias("a")->join("xsh_user b", "a.uid=b.id")->where("b.pid", $value)->where("b.level", '>=', $uinfo["level"])->count();
                    if ($sonLevelCount >= 3) {
                        Db::name("user")->where(["id" => $value])->update(["level" => $uinfo["level"] + 1]);
                        $this->parentslevel($value, $uinfo["level"] + 1);
                        $this->uselfLevel($value, $uinfo["level"] + 1);
                    }
                }
            }
        }
    }

    public function parentslevel($uid, $level) {
        $uinfo = Db::name("user")->where(["id" => $uid])->find();
        $pidss = explode(",", $uinfo["pids"]);
        $count = Db::name("user")->where(["pid" => $uinfo["pid"]])->where('level', ">=", $level)->count();
// dump($count);
        if ($count >= 3) {
            Db::name("user")->where(["id" => $uinfo["pid"]])->update(["level" => $level + 1]);
            $this->parentslevel($uinfo["pid"], $level + 1);
        }
    }

    public function uselfLevel($uid, $level) {
        $count = Db::name("user")->where(["pid" => $uid])->where('level', ">=", $level)->count();
        if ($count >= 3) {
            Db::name("user")->where(["id" => $uinfo["pid"]])->update(["level" => $level + 1]);
            $this->parentslevel($uinfo["pid"], $level + 1);
        }
    }

    public function paretsFunds($uid, $money) {
        $uInfo = \app\common\model\User::get($uid);
        $pinfo = \app\common\model\User::get($uInfo["pid"]);
        $pids = explode(',', $uInfo["pids"]);
        $level = $pinfo["level"] > 0 ? $pinfo["level"] : 0;
        $a = 2;
        foreach ($pids as $key => $value) {
            $levelinfo = Db::name("level_config")->where(["level" => 1])->find();
            $info = Db::name("user")->where(["id" => $value])->find();
            if ($info and $info["level"] > 0) {
                if ($a == 2) {
                    $this->bonus($value, $money * $levelinfo["rate1"] * 0.8, '直推奖励_' . $uid, 11);
                    \app\common\model\User::score($money * $levelinfo["rate1"] * 0.1, $value, '直推奖励_增加10%积分');
                    \app\common\model\User::buycoupon($money * $levelinfo["rate1"] * 0.1, $value, '直推奖励_增加10%消费券');
                    $this->ManegeFunds($value, $money * $levelinfo["rate1"] * 0.8);
                }
                if ($a == 1) {
                    $this->bonus($value, $money * $levelinfo["rate2"] * 0.8, '间推奖励_' . $uid, 11);
                    \app\common\model\User::score($money * $levelinfo["rate2"] * 0.1, $value, '间推奖励_增加10%积分');
                    \app\common\model\User::buycoupon($money * $levelinfo["rate2"] * 0.1, $value, '直推奖励_增加10%消费券');
                    $this->ManegeFunds($value, $money * $levelinfo["rate2"] * 0.8);
                }
                $a--;
            }
        }
    }

    public function ManegeFunds($uid, $money) {
        $uInfo = \app\common\model\User::get($uid);
        $pinfo = \app\common\model\User::get($uInfo["pid"]);
        if ($pinfo) {
            if ($pinfo["level"] > 0) {
                $level = $pinfo["level"] > 0 ? $pinfo["level"] : 0;
                $levelinfo = Db::name("level_config")->where(["level" => 1])->find();
                $this->bonus($pinfo["id"], $money * 0.1 * 0.8, '管理奖励_' . $uid, 13);
                \app\common\model\User::score($money * 0.1 * 0.1, $pinfo["id"], '管理奖励_增加10%积分');
                \app\common\model\User::buycoupon($money * 0.1 * 0.1, $pinfo["id"], '直推奖励_增加10%消费券');
            }
        }
    }

    public static function updateTeamListXm($uid, $oid) {
        $oid = $oid[0];
        $oderinfo = \app\api\model\wanlshop\Order::get($oid);
        $GoodsInfo = \app\index\model\wanlshop\OrderGoods::get(["order_id" => $oid]);
        $GoodsCatInfo = \app\api\model\wanlshop\Goods::get($GoodsInfo["goods_id"]);
        $price = \app\index\model\wanlshop\GoodsSku::get($GoodsInfo["goods_sku_id"])["price"];
        $uinfo = \app\common\model\User::get($uid);
        if (substr($oderinfo["order_no"], strlen($oderinfo["order_no"]) - 1, 1) == 'Q') {
            
        } else {
            $this->sendfunds1680($uid, $GoodsInfo["goods_id"], $price);
        }
    }

    private function sendfundsGoods($uid, $gid, $price) {
        $pinfo = \app\common\model\User::get($uid)["pids"];
        $succ = explode(',', $getFundsInfo["succ"]);
        $fail = explode(',', $getFundsInfo["fail"]);
        $pids = explode(',', $pinfo);
        $truepids = array_reverse($pids, true);
        $a = 0;
        $s = 1;
        foreach ($truepids as $k => $v) {
            $s = \app\common\model\User::get($v);
            if ($s < 20) {
                if ($s and $s["grade"] == 1) {
                    if ($s == 1) {
                        $this->bonus($v, $money * $succ[21], '直推奖励', 1);
                    }
                    $money = $succ[$a++] * $price;
                    $this->bonus($v, $money, '贡献金奖励' . $s++ . "级", 1);
                }
            } else {
                break;
            }
        }
    }

    private function sendfunds1680($uid, $gid, $price) {
        $pinfo = \app\common\model\User::get($uid)["pids"];
        $succ = explode(',', $getFundsInfo["succ"]);
        $fail = explode(',', $getFundsInfo["fail"]);
        $pids = explode(',', $pinfo);
        $truepids = array_reverse($pids, true);
        $a = 0;
        $s = 1;
        foreach ($truepids as $k => $v) {
            $s = \app\common\model\User::get($v);
            if ($s < 20) {
                if ($s and $s["grade"] == 1) {
                    $money = $succ[$a++] * $price;
                    $this->bonus($s, $money, '贡献金奖励' . $s++ . "级", 1);
                }
            } else {
                break;
            }
        }
    }

    public static function bonus($uid, $getBalance, $meno = "奖励", $souce = 99) {
        $pinfo = \app\common\model\User::get($uid);
//$pinfo->setInc("money", $getBalance);
        $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
        $forLog->money($getBalance, $uid, $meno, 'sys', $souce);
    }

    public function Contribution($money, $user_id, $memo, $type = '', $ids = '') {
        $user = \app\common\model\User::get($user_id);
        if ($user && $money != 0) {
            $before = $user["contribute"];
            $after = function_exists('bcadd') ? bcadd($user["contribute"], $money, 2) : $user["contribute"] + $money;
//更新会员信息
            $user->save(['contribute' => $after]);
//写入日志
            $row = Db::name("user_contribute_log")->insert([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'createtime' => time(),
                'service_ids' => $ids // 业务ID
            ]);
// return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

    public function fenhong_instance($uid, $money, $flag, $cangetMoney) {

        $fen = Db::name("fenhong")->where(["uid" => $uid])->find();
        $user = \app\common\model\User::get($uid);
        if ($flag == 1) {
            $cangetMoney = $cangetMoney * 0.03;
        }
        if (!$fen) {
            Db::name("fenhong")->insert([
                'uid' => $uid,
                'total_fenhong' => $cangetMoney, // 操作金额
                'remain_fenhong' => $cangetMoney, // 原金额
                'had_fenhong' => 0, // 增加后金额
                'max_money' => $money,
                'remain_money' => $money,
                'addtime' => date('Y-m-d', time()), // 业务ID
                'updatetime' => time(), // 业务ID
            ]);
            Db::name("fenhong_yestoday")->insert([
                'uid' => $uid,
                'money' => $cangetMoney, // 操作金额
                'addtime' => date('Y-m-d', time()), // 业务ID
                'ordermoney' => $money
            ]);
        } else {
            $getRate = \app\api\controller\Tools::getConfig1("sys_rate");
            $max_money = function_exists('bcadd') ? bcadd($fen["max_money"], $money, 2) : $fen["max_money"] + $money;
            $remain_money = function_exists('bcadd') ? bcadd($fen["remain_money"], $money, 2) : $fen["max_money"] + $money;
            $total_fenhong = function_exists('bcadd') ? bcadd($fen["total_fenhong"], $cangetMoney, 2) : $fen["total_fenhong"] + $cangetMoney;
            $remain_fenhong = function_exists('bcadd') ? bcadd($fen["remain_fenhong"], $cangetMoney, 2) : $fen["remain_fenhong"] + $cangetMoney;
//更新用户实例
            Db::name("fenhong")->where(["uid" => $uid])->update(['total_fenhong' => $total_fenhong, 'remain_fenhong' => $remain_fenhong, 'max_money' => $max_money, 'remain_money' => $remain_money]);
            $yestoday = Db::name("fenhong_yestoday")->where(["uid" => $uid])->find();
            if ($yestoday) {
                $had_yestoday = Db::name("fenhong_yestoday")->where(["uid" => $uid, 'addtime' => date('Y-m-d', time())])->find();
                if ($had_yestoday) {
                    Db::name("fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $had_yestoday["money"] + $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money]);
                } else {
                    Db::name("fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money]);
                }
            }
        }
//操作奖池
        if ($flag == 0) {
//进入奖池10%
            $getRate = \app\api\controller\Tools::getConfig1("sys_rate");
            $funds = $money * $getRate;
            Db::name("zconfig")->where(["key" => 'Jackpot'])->setInc("value", $funds);
            $this->Jackpot_log($uid, $funds, '购物入池', 'sys', $uid);
            $this->Instance_log($uid, $cangetMoney, '购物增加分红权');
//操作奖池流水表
            $date = date('Y-m-d', time());
            $exist = Db::name("fenhong_instance")->where(["calctime" => $date])->find();
            if ($exist) {
                $f = $exist["fenhong"] + $cangetMoney;
                $s = $exist["sys_fenhong"] + $cangetMoney;
                Db::name("fenhong_instance")->where(["calctime" => $date])->update(["fenhong" => $f, "sys_fenhong" => $s]);
            } else {
                $last = Db::name("fenhong_instance")->order("id desc")->find();
                $s = $last["sys_fenhong"] + $cangetMoney;
                Db::name("fenhong_instance")->insert(["fenhong" => $cangetMoney, "sys_fenhong" => $s, 'calctime' => $date]);
            }
        }
    }

    public function agent_fenhong_instance($uid, $money, $flag, $cangetMoney) {
        $fen_agent = Db::name("agent_fenhong")->where(["uid" => $uid])->find();
        $user = \app\common\model\User::get($uid);
        if (!$fen_agent) {

            Db::name("agent_fenhong")->insert([
                'uid' => $uid,
                'total_fenhong' => $cangetMoney, // 操作金额
                'remain_fenhong' => $cangetMoney, // 原金额
                'had_fenhong' => 0, // 增加后金额
                'max_money' => $money,
                'remain_money' => $money,
                'addtime' => date('Y-m-d', time()), // 业务ID
                'updatetime' => time(), // 业务ID
            ]);
            Db::name("agent_fenhong_yestoday")->insert([
                'uid' => $uid,
                'money' => $cangetMoney, // 操作金额
                'addtime' => date('Y-m-d', time()), // 业务ID
                'ordermoney' => $money
            ]);
        } else {
            $fen = Db::name("agent_fenhong")->where(["uid" => $uid])->find();
            $max_money = function_exists('bcadd') ? bcadd($fen["max_money"], $money * 0.03, 2) : $fen["max_money"] + $money * 0.03;
            $remain_money = function_exists('bcadd') ? bcadd($fen["remain_money"], $money * 0.03, 2) : $fen["max_money"] + $money * 0.03;
            $total_fenhong = function_exists('bcadd') ? bcadd($fen["total_fenhong"], $cangetMoney, 2) : $fen["total_fenhong"] + $cangetMoney;
            $remain_fenhong = function_exists('bcadd') ? bcadd($fen["remain_fenhong"], $cangetMoney, 2) : $fen["remain_fenhong"] + $cangetMoney;
//更新用户实例
            Db::name("fenhong")->where(["uid" => $uid])->update(['total_fenhong' => $total_fenhong, 'remain_fenhong' => $remain_fenhong, 'max_money' => $max_money, 'remain_money' => $remain_money]);
            $yestoday = Db::name("fenhong_yestoday")->where(["uid" => $uid])->find();
            if ($yestoday) {
                $had_yestoday = Db::name("fenhong_yestoday")->where(["uid" => $uid, 'addtime' => date('Y-m-d', time())])->find();
                if ($had_yestoday) {
                    Db::name("fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $had_yestoday["money"] + $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money * 0.03]);
                } else {
                    Db::name("fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money * 0.03]);
                }
            }
            $fen = Db::name("agent_fenhong")->where(["uid" => $uid])->find();
            $max_money = function_exists('bcadd') ? bcadd($fen["max_money"], $money, 2) : $fen["max_money"] + $money;
            $remain_money = function_exists('bcadd') ? bcadd($fen["remain_money"], $money, 2) : $fen["max_money"] + $money;
            $total_fenhong = function_exists('bcadd') ? bcadd($fen["total_fenhong"], $cangetMoney, 2) : $fen["total_fenhong"] + $cangetMoney;
            $remain_fenhong = function_exists('bcadd') ? bcadd($fen["remain_fenhong"], $cangetMoney, 2) : $fen["remain_fenhong"] + $cangetMoney;
//更新用户实例
            Db::name("agent_fenhong")->where(["uid" => $uid])->update(['total_fenhong' => $total_fenhong, 'remain_fenhong' => $remain_fenhong, 'max_money' => $max_money, 'remain_money' => $remain_money]);
            $yestoday = Db::name("agent_fenhong_yestoday")->where(["uid" => $uid])->find();
            if ($yestoday) {
                $had_yestoday = Db::name("agent_fenhong_yestoday")->where(["uid" => $uid, 'addtime' => date('Y-m-d', time())])->find();
                if ($had_yestoday) {
                    Db::name("agent_fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $had_yestoday["money"] + $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money]);
                } else {
                    Db::name("agent_fenhong_yestoday")->where(["uid" => $uid])->update(["money" => $cangetMoney, 'addtime' => date('Y-m-d', time()), 'ordermoney' => $had_yestoday["money"] + $money]);
                }
            }
        }
//操作奖池
        $funds = $money * 0.1;
        Db::name("zconfig")->where(["key" => 'Agent_Jackpot'])->setInc("value", $funds);
        $this->Jackpot_log($uid, $funds, '代理购物入池', 'sys', $uid);
        $this->Instance_log($uid, $cangetMoney, '代理购物增加分红权');
//操作奖池流水表
        $date = date('Y-m-d', time());
        $exist = Db::name("agent_fenhong_instance")->where(["calctime" => $date])->find();
        if ($exist) {
            $f = $exist["fenhong"] + $cangetMoney;
            $s = $exist["sys_fenhong"] + $cangetMoney;
            Db::name("agent_fenhong_instance")->where(["calctime" => $date])->update(["fenhong" => $f, "sys_fenhong" => $s]);
        } else {
            $last = Db::name("agent_fenhong_instance")->order("id desc")->find();
            $s = $last["sys_fenhong"] + $cangetMoney;
            Db::name("agent_fenhong_instance")->insert(["fenhong" => $cangetMoney, "sys_fenhong" => $s, 'calctime' => $date]);
        }
    }

    public function calc_fenhong($uid, $money, $flag = 0) {
        if ($flag == 0) {
            Db::name("zconfig")->where(["key" => 'Jackpot'])->setDec("value", $money);
            $fen = Db::name("fenhong")->where(["uid" => $uid])->find();
            $remain_fenhong = function_exists('bcsub') ? bcsub($fen["remain_fenhong"], $money, 2) : $fen["remain_fenhong"] - $money;
            $had_fenhong = function_exists('bcadd') ? bcadd($fen["had_fenhong"], $money, 2) : $fen["had_fenhong"] + $money;
            Db::name("fenhong")->where(["uid" => $uid])->update(['remain_fenhong' => $remain_fenhong, 'had_fenhong' => $had_fenhong]);
        } else {
            Db::name("zconfig")->where(["key" => 'Agent_Jackpot'])->setDec("value", $money);
            $fen = Db::name("agent_fenhong")->where(["uid" => $uid])->find();
            $remain_fenhong = function_exists('bcsub') ? bcsub($fen["remain_fenhong"], $money, 2) : $fen["remain_fenhong"] - $money;
            $had_fenhong = function_exists('bcadd') ? bcadd($fen["had_fenhong"], $money, 2) : $fen["had_fenhong"] + $money;
            Db::name("agent_fenhong")->where(["uid" => $uid])->update(['remain_fenhong' => $remain_fenhong, 'had_fenhong' => $had_fenhong]);
        }
// Db::name("fenhong")->where(["uid" => $uid])->update(['had_fenhong' => $had_fenhong, 'remain_fenhong' => $remain_fenhong]);
        $a = 10;
        if ($flag == 1) {
            $a = 31;
        }
        $this->bonus($uid, $money * 0.8, "分红值释放", $a);
        \app\common\model\User::score($money * 0.1, $uid, '分红值_增加10%积分');
        $u = \app\common\model\User::get($uid);
        \app\common\model\User::buycoupon($money * 0.1, $uid, '分红值_增加10%积分');
        Db::name("user")->where(["id" => $uid])->update(["buycoupon" => $u["buycoupon"] + $money * 0.1]);
    }

    public static function Instance_log($user_id, $money, $memo, $type = '') {
        $instance = Db::name("fenhong_instance")->order("id desc")->find();
        if ($money != 0) {
            if ($instance) {
                $before = $instance["sys_fenhong"] - $money;
                $after = $instance["sys_fenhong"];
            } else {
                $before = 0;
                $after = $money;
            }

// $after = function_exists('bcadd') ? bcadd($instance["sys_fenhong"], $money, 2) : $instance["sys_fenhong"] + $money;
//写入日志
            $row = Db::name("fenhong_log")->insert([
                'user_id' => $user_id,
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

    public static function Jackpot_log($user_id, $money, $memo, $type = 'sys', $oid) {
        $instance = \app\api\controller\Tools::getConfig1("Jackpot");
        if ($money != 0) {
            $before = $instance - $money;
            $after = $instance;
//写入日志
            $row = Db::name("jackpot_log")->insert([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'oid' => $oid,
                'createtime' => time()
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

}

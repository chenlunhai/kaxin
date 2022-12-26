<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace app\common\controller;

use think\Db;

/**
 * Description of Dosth
 *
 * @author Cc
 */
class Dosth {

    //put your code here


    public function updateday() {

        $list = Db::name("user_team")->where("1=1")->select();
        foreach ($list as $key => $value) {
            Db::name("user_team")->where(["id" => $value["id"]])->update(["yestodayorder" => $value["todayorder"]]);
        }
        Db::name("user_team")->where("1=1")->update(["todayorder" => 0]);
    }

    public function dorelease() {

        $list = Db::table("static_instance")->where("1=1")->where("remain_num", ">", 0)->select();
        // $list = Db::table("static_instance")->where("user_id=2980")->where("remain_num", ">", 0)->select();
        $taxInfo = $this->getConfig('release_rate');
        if ($taxInfo) {
            $tax = $taxInfo["s_value"];
        } else {
            $tax = 0.002;
        }
        $diress = $this->getConfig('distribution');
        $delaytime = $this->zgetConfig('delaytime');
        var_dump($delaytime);
        $dire = $diress["s_value"];
        $first = 1; //explode(":", $dire)[0];
        $second = 1; // explode(":", $dire)[1];

        foreach ($list as $key => $value) {
            if (time() < $value["create_time"] + $delaytime * 86400) {
                return;
            }
            $uinfo = Db::name("user")->where(["id" => $value["user_id"]])->find();
            if ($uinfo["noget"] != 'hidden') {
                if ($uinfo["score"] > 0) {
                    $score = $value['total_num'] * $uinfo["score"] / 30;
                } else {
                    $score = $value['total_num'] * 0.1 / 30;
                }
                if ($score <= $value["remain_num"]) {
                    self::money($score * $first, $value["user_id"], "每日收益", 'sys', 1);
                    // self::score($value["user_id"], $score * $second, "每日静态释放", 'sys', 1, 1);
                    Db::table("static_instance")->where(["id" => $value["id"]])->setDec("remain_num", $score);
                    Db::table("static_instance")->where(["id" => $value["id"]])->setInc("was_num", $score);
                    if ($uinfo["onlystatic"] == 'hidden') {
                        $this->fatherlistfunds($value["user_id"], $score, $first, $second);
                        ///$this->liudan($value["user_id"], $score, $first, $second, $value["create_time"]);
                    }
                } else {
                    if ($value["remain_num"] > 0) {
                        self::money($value["user_id"], $value["remain_num"] * $first, "每日收益", 'sys', 1);
                        //  self::score($value["user_id"], $value["remain_num"] * $second, "每日静态释放", 'sys', 1, 1);
                        Db::table("static_instance")->where(["id" => $value["id"]])->setDec("remain_num", $value["remain_num"]);
                        Db::table("static_instance")->where(["id" => $value["id"]])->setInc("was_num", $value["remain_num"]);
                        if ($uinfo["onlystatic"] == 'hidden') {
                            $this->fatherlistfunds($value["user_id"], $value["remain_num"], $first, $second);
                            // $this->liudan($value["user_id"], $score, $first, $second, $value["create_time"]);
                        }
                    }
                }
            }
        }
    }

    public function fatherlistfunds($uid, $money, $first, $second) {

        $uInfo = Db::name("user")->where(["id" => $uid])->find();
        $uccname = $uInfo["nickname"];
        $pids = explode(",", $uInfo["pids"]);
        dump($money);
        dump($pids);
//        if ($pids) {
//            if (isset($pids[0]) and $pids[0] > 0) {
//                $taxInfo = $this->getConfig('direct_rate');
//                if ($taxInfo) {
//                    $tax = $taxInfo["s_value"];
//                } else {
//                    $tax = 0.5;
//                }
//                $tax = 0.2;
//                $pInfo = [];
//                $score = 0;
//                $pInfo = Db::table("static_instance")->where(["user_id" => $pids[0]])->where("remain_num", ">", 0)->find();
//                if ($pInfo and $uInfo["nodyc"] == 'normal') {
//                    if ($pInfo["remain_num"] >= $money * $tax) {
//                        $score = $money * $tax;
//                    } else {
//                        $score = $pInfo["remain_num"];
//                    }
//                    self::money($score * $first, $pids[0], "直推奖励__" . $uid . "_" . $uccname, 'sys', 2);
//                    // self::score($pids[0], $score * $second, "直推奖励__" . $uid . "_" . $uccname, 'sys', 2, 2);
//                    Db::table("static_instance")->where(["id" => $pInfo["id"]])->setDec("remain_num", $score);
//                    Db::table("static_instance")->where(["id" => $pInfo["id"]])->setInc("was_num", $score);
//                   $ppinfo= Db::name("user")->where(["id"=>$pids[0]])->find();
//                    if($ppinfo["level"]>1){
//                        $this->fathermanage($pids[0],$score * $first);
//                    }
//                    
//                }
//            }
        //暂时注释 。以后备用间推奖励
        //            if (isset($pids[1]) and $pids[1] > 0) {
        //                $taxInfo = $this->getConfig('push_rate');
        //                if ($taxInfo) {
        //                    $tax = $taxInfo["value"];
        //                } else {
        //                    $tax = 0.1;
        //                }
        //                  $pInfo=[];
        //                $score=0;
        //                $pInfo22 = Db::table("static_instance")->where(["user_id" => $pids[1]])->where("remain_num", ">", 0)->find();
        //                if ($pInfo22 and $uInfo["nodyc"] == 'normal') {
        //                    if ($pInfo22["remain_num"] >= $money * $tax) {
        //                        $score = $money * $tax;
        //                    } else {
        //                        $score = $pInfo22["remain_num"];
        //                    }
        ////                    echo "间推推_".$pids[0]."_积分_".$score."_剩余_".$pInfo["remain_num"]."_来源id_".$pInfo["id"]."_直推id_".$pids[0]."<br/>";
        //                    self::money($score * $first, $pids[1], "间推奖励__" . $uid . "_" . $uccname, 'sys', 3);
        //                    self::score($pids[1], $score * $second, "间推奖励__" . $uid . "_" . $uccname, 'sys', 3, 3);
        //                    Db::table("static_instance")->where(["id" => $pInfo22["id"]])->setDec("remain_num", $score);
        //                    Db::table("static_instance")->where(["id" => $pInfo22["id"]])->setInc("was_num", $score);
        //                }
        //            }
        $aj = [0.1, 0.15, 0.2, 0.25, 0.3];
        $sa = false;
        $m = 1;
        $level = $frontrate = $templevel = $temp = 0;
        foreach ($pids as $key => $value) {
            $un = Db::name("user")->where(["id" => $value])->find();
            $pInfo = [];
            $score = 0;
            if (true) {
                $rate = $un["score"];
                if ($rate == 0) {
                    $rate = 0.1;
                }
                if ($temp < $rate) {
                    $frontrate = $rate - $temp;
                    $pInfo33 = Db::table("static_instance")->where(["user_id" => $value])->where("remain_num", ">", 0)->find();
                    if ($pInfo33 and $uInfo["nodyc"] == 'normal') {
                        if ($pInfo33["remain_num"] >= $money * $frontrate) {
                            $score = $money * $frontrate;
                        } else {
                            $score = $pInfo33["remain_num"];
                        }
                        self::money($score * $first, $value, "团队奖励__" . $uid . "_" . $uccname, 'sys', 5);
                        //self::score($value, $score * $second, "团队奖励__" . $uid . "_" . $uccname, 'sys', 5, 5);
                        Db::table("static_instance")->where(["id" => $pInfo33["id"]])->setDec("remain_num", $score);
                        Db::table("static_instance")->where(["id" => $pInfo33["id"]])->setInc("was_num", $score);
                        $this->fathermanageLevel($value, $score * $first);
                        $sa = false;
                        $m++;
                    }
                    $temp = $rate;
                } else {
//                        if ($temp > 0 and $temp == $aj[$un["level"] - 2] and !$sa and $un["level"] > 0) {
//                            $pInfo44 = Db::table("static_instance")->where(["user_id" => $value])->where("remain_num", ">", 0)->find();
//                           
//                            if ($pInfo44 and $uInfo["nodyc"] == 'normal') {
//                                   
//                               $frontrate=0.2;
//                                if ($pInfo44["remain_num"] >= $money * $frontrate) {
//                                    $score = $money * $frontrate;
//                                } else {
//                                    $score = $pInfo44["remain_num"];
//                                }
//                          
//                                self::money($score * $first * 0.1, $value, "管理奖励__" . $uid . "_" . $uccname, 'sys', 6);
//                                //  self::score($value, $score * $second * 0.1, "平级奖励__" . $uid . "_" . $uccname, 'sys', "_" . $uid . "_" . $uccname, 6);
//                                Db::table("static_instance")->where(["id" => $pInfo44["id"]])->setDec("remain_num", $score);
//                                Db::table("static_instance")->where(["id" => $pInfo44["id"]])->setInc("was_num", $score);
//                               // $this->fathermanageLevel($value,$score * $first);
//                                $sa = true;
//                                $m = 1;
//                            }
//                        }
                    // }
                }
            }
        }
    }

    public function fathermanage($uid, $money) {
        $uinfo = Db::name("user")->where(["id" => $uid])->find();
        if ($uinfo["pid"] > 0) {
            $pinfo = Db::name("user")->where(["id" => $uinfo["pid"]])->find();
            if ($uinfo["level"] == $pinfo["level"]) {
                self::money($money * 0.2, $pinfo["id"], "管理奖励__" . $uid . "_" . $uinfo["nickname"], 'sys', 6);
                Db::table("static_instance")->where(["id" => $pinfo["id"]])->setDec("remain_num", $money * 0.2);
                Db::table("static_instance")->where(["id" => $pinfo["id"]])->setInc("was_num", $money * 0.2);
            }
        }
    }

    public function fathermanageLevel($uid, $money) {
        $uinfo = Db::name("user")->where(["id" => $uid])->find();
        if ($uinfo["pid"] > 0) {
            $pinfo = Db::name("user")->where(["id" => $uinfo["pid"]])->find();
            if ($pinfo["level"] > 1) {
                self::money($money * 0.2, $pinfo["id"], "管理奖励__" . $uid . "_" . $uinfo["nickname"], 'sys', 6);
                Db::table("static_instance")->where(["id" => $pinfo["id"]])->setDec("remain_num", $money * 0.2);
                Db::table("static_instance")->where(["id" => $pinfo["id"]])->setInc("was_num", $money * 0.2);
            }
        }
    }

    public function liudan($uid, $money, $first, $second, $time) {

        $calc = (time() - $time) / 86400;
        $day = ($calc - 1) % 9;
        $uInfo = Db::name("user")->where(["id" => $uid])->find();
        $uccname = $uInfo["nickname"];
        $pids = explode(",", $uInfo["pids"]);
        $tax = 0.3;
        $cout = count($pids);
        if ($cout > $day) {

            $loopList = Db::name("user_loop")->where(["uid" => $pids[$day]])->find();

            if ($loopList) {
                $pInfo = [];
                $score = 0;
                $temp = $pids[$day];
                $pInfo = Db::table("static_instance")->where(["user_id" => $temp])->where("remain_num", ">", 0)->find();
                if ($pInfo and $uInfo["nodyc"] == 'normal') {
                    if ($pInfo["remain_num"] >= $money * $tax) {
                        $score = $money * $tax;
                    } else {
                        $score = $pInfo["remain_num"];
                    }
                    self::money($score * $first, $temp, "流单奖励__" . $uid . "_" . $uccname . "_" . $day, 'sys', 4);
                    self::score($temp, $score * $second, "流单奖励__" . $uid . "_" . $uccname . "_" . $day, 'sys', 4, 4);
                    Db::table("static_instance")->where(["id" => $pInfo["id"]])->setDec("remain_num", $score);
                    Db::table("static_instance")->where(["id" => $pInfo["id"]])->setInc("was_num", $score);
                }
            }
        }
    }

    public static function money($money, $user_id, $memo, $type = '', $ids = '', $oid = 0) {
        // $user = model('app\common\model\User')->get($user_id);
        //  $useobj = new \app\common\model\UserSub([],$num);
        //$user = $useobj->where('id',$user_id)->find();
        $user = Db::name("user")->where(["id" => $user_id])->find();
        if ($user && $money != 0) {
            $before = $user["money"];
            $after = function_exists('bcadd') ? bcadd($user["money"], $money, 2) : $user["money"] + $money;
            //更新会员信息
            Db::name("user")->where(["id" => $user_id])->update(['money' => $after]);
            //写入日志
            $row = model('app\common\model\MoneyLog')->create([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'service_ids' => $ids, // 业务ID
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

    public static function score($user_id, $money, $memo, $type = '', $ids = '', $source = 0) {
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
                        'source' => $source,
                        'service_ids' => $ids // 业务ID
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更积分失败'];
        }
    }

    public function zgetConfig($cname) {

        $configInfo = Db::name("zconfig")->where(["key" => $cname])->find();
        if ($configInfo) {
            return $configInfo["value"];
        } else {
            return [];
        }
    }

    public function getConfig($cname) {

        $configInfo = Db::name("system_config")->where(["s_key" => $cname])->find();
        if ($configInfo) {
            return $configInfo;
        } else {
            return [];
        }
    }

}

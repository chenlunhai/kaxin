<?php

namespace addons\signin\library;

use addons\signin\model\Signin;
use app\common\library\Auth;
use DateTime;
use fast\Date;
use stdClass;
use think\Db;

class Service
{
    /**
     * 获取排名信息
     *
     * @param int $user_id
     * @return array
     */
    public static function getRankInfo($user_id = null)
    {
        $user_id = is_null($user_id) ? Auth::instance()->id : $user_id;
        $rankList = Signin::with(["user"])
            ->where("createtime", ">", \fast\Date::unixtime('day', -1))
            ->field("user_id,MAX(successions) AS days")
            ->group("user_id")
            ->order("days DESC,createtime ASC")
            ->limit(10)
            ->select();
        foreach ($rankList as $index => $datum) {
            $datum->getRelation('user')->visible(['id', 'username', 'nickname', 'avatar']);
        }

        $ranking = 0;
        $lastdata = Signin::where('user_id', $user_id)->order('createtime', 'desc')->find();
        //是否已签到
        $checked = $lastdata && $lastdata['createtime'] >= Date::unixtime('day') ? true : false;
        //连续登录天数
        $successions = $lastdata && $lastdata['createtime'] >= Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
        if ($successions > 0) {
            //优先从列表中取排名
            foreach ($rankList as $index => $datum) {
                if ($datum->user_id == $user_id) {
                    $ranking = $index + 1;
                    break;
                }
            }
            if (!$ranking) {
                $prefix = config('database.prefix');
                $ret = Db::query("SELECT COUNT(*) nums FROM (SELECT user_id,MAX(successions) days FROM `{$prefix}signin` WHERE createtime > " . Date::unixtime('day', -1) . " GROUP BY user_id ORDER BY days) AS dd WHERE dd.days >= " . $successions);
                $ranking = $ret[0]['nums'] ?? 0;
            }
        }
        return [$rankList, $ranking, $successions, $checked];
    }
}

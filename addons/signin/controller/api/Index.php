<?php

namespace addons\signin\controller\api;

use addons\signin\library\Service;
use addons\signin\model\Signin;
use fast\Date;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Index extends Base
{
    /**
     * 签到首页
     * @return string
     */
    public function index()
    {
        $config = get_addon_config('signin');
        $signdata = $config['signinscore'];

        list($ranklist, $self_rank, $successions, $is_signin) = Service::getRankInfo();

        $success_day = $successions + 1;
        $score = isset($signdata['s' . $success_day]) ? $signdata['s' . $success_day] : $signdata['sn'];

        $msg = $successions ? "你当前已经连续签到 {$successions} 天,明天继续签到可获得 {$score} 积分" : "今天签到可获得 {$score} 积分,请点击签到领取积分";

        $this->success('', [
            'signinscore' => $config['signinscore'], //签到规则
            'isfillup'    => $config['isfillup'], //是否开启补签
            'fillupscore' => $config['fillupscore'], //补签消耗积分
            'fillupdays'  => $config['fillupdays'], //允许补签天数
            'ranklist'    => $ranklist,
            'successions' => $successions,
            'is_signin'   => $is_signin,
            'self_rank'   => $self_rank, //自己的排名
            'score'       => $this->auth->score, //用户积分
            'msg'         => $msg
        ]);
    }

    /**
     * 每月签到情况
     * @return void
     */
    public function monthSign()
    {

        $date = $this->request->request('date', date("Y-m"), "trim");
        $time = strtotime($date);
        $list = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->field('id,createtime')
            ->whereTime('createtime', 'between', [date("Y-m-1", $time), date("Y-m-1", strtotime("+1 month", $time))])
            ->select();
        $newData = [];
        foreach ($list as $index => $item) {
            $newData[date('d', $item->createtime)] = date('d', $item->createtime);
        }
        $this->success('', $newData);
    }

    /**
     * 立即签到
     */
    public function dosign()
    {

        $config = get_addon_config('signin');
        $signdata = $config['signinscore'];

        $lastdata = \addons\signin\model\Signin::where('user_id', $this->auth->id)->order('createtime', 'desc')->find();
        $successions = $lastdata && $lastdata['createtime'] > Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
        $signin = \addons\signin\model\Signin::where('user_id', $this->auth->id)->whereTime('createtime', 'today')->find();
        if ($signin) {
            $this->error('今天已签到,请明天再来!');
        } else {
            $successions++;
            $score = isset($signdata['s' . $successions]) ? $signdata['s' . $successions] : $signdata['sn'];
            Db::startTrans();
            try {
                \addons\signin\model\Signin::create(['user_id' => $this->auth->id, 'successions' => $successions, 'createtime' => time()]);
                \app\common\model\User::score($score, $this->auth->id, "连续签到{$successions}天");
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error('签到失败,请稍后重试');
            }
            $this->success('签到成功!连续签到' . $successions . '天!获得' . $score . '积分');
        }
    }

    /**
     * 签到补签
     */
    public function fillup()
    {
        $date = $this->request->request('date');
        $time = strtotime($date);
        $config = get_addon_config('signin');
        if (!$config['isfillup']) {
            $this->error('暂未开启签到补签');
        }
        if ($time > time()) {
            $this->error('无法补签未来的日期');
        }
        if ($config['fillupscore'] > $this->auth->score) {
            $this->error('你当前积分不足');
        }
        $days = Date::span(time(), $time, 'days');
        if ($config['fillupdays'] < $days) {
            $this->error("只允许补签{$config['fillupdays']}天的签到");
        }
        $count = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->where('type', 'fillup')
            ->whereTime('createtime', 'between', [Date::unixtime('month'), Date::unixtime('month', 0, 'end')])
            ->count();
        if ($config['fillupnumsinmonth'] <= $count) {
            $this->error("每月只允许补签{$config['fillupnumsinmonth']}次");
        }
        Db::name('signin')->whereTime('createtime', 'd')->select();
        $signin = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->where('type', 'fillup')
            ->whereTime('createtime', 'between', [$date, date("Y-m-d 23:59:59", $time)])
            ->count();
        if ($signin) {
            $this->error("该日期无需补签到");
        }
        $successions = 1;
        $prev = $signin = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->whereTime('createtime', 'between', [date("Y-m-d", strtotime("-1 day", $time)), date("Y-m-d 23:59:59", strtotime("-1 day", $time))])
            ->find();
        if ($prev) {
            $successions = $prev['successions'] + 1;
        }
        Db::startTrans();
        try {
            \app\common\model\User::score(-$config['fillupscore'], $this->auth->id, '签到补签');
            //寻找日期之后的
            $nextList = \addons\signin\model\Signin::where('user_id', $this->auth->id)
                ->where('createtime', '>=', strtotime("+1 day", $time))
                ->order('createtime', 'asc')
                ->select();
            foreach ($nextList as $index => $item) {
                //如果是阶段数据，则中止
                if ($index > 0 && $item->successions == 1) {
                    break;
                }
                $day = $index + 1;
                if (date("Y-m-d", $item->createtime) == date("Y-m-d", strtotime("+{$day} day", $time))) {
                    $item->successions = $successions + $day;
                    $item->save();
                }
            }
            \addons\signin\model\Signin::create(['user_id' => $this->auth->id, 'type' => 'fillup', 'successions' => $successions, 'createtime' => $time + 43200]);
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error('补签失败,请稍后重试');
        } catch (Exception $e) {
            Db::rollback();
            $this->error('补签失败,请稍后重试');
        }
        $this->success('补签成功');
    }

    /**
     * 签到日志
     * @return void
     */
    public function signLog()
    {
        $list = \addons\signin\model\Signin::where('user_id', $this->auth->id)
            ->field('id,successions,type,createtime')->order('createtime desc')->paginate(15);
        foreach ($list as $item) {
            $item->createtime = date('Y-m-d', $item->createtime);
            $item->type = $item->type == 'fillup' ? '补签' : '签到';
        }
        $this->success('', $list);
    }

    /**
     * 排行榜
     */
    public function rank()
    {
        list($ranklist, $self_rank, $successions) = Service::getRankInfo();

        $this->success("", [
            'ranklist'    => $ranklist,
            'self_rank'   => $self_rank, //自己的排名
            'successions' => $successions //自己的签到
        ]);
    }
}

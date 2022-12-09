<?php

namespace app\common\library;

use think\Hook;
use think\Cache;

/**
 * 邮箱验证码类
 */
class Ems
{

    /**
     * 验证码有效时长
     * @var int
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int
     */
    protected static $maxCheckNums = 10;

    /**
     * 获取最后一次邮箱发送的数据
     *
     * @param   int    $email 邮箱
     * @param   string $event 事件
     * @return  Ems
     */
    public static function get($email, $event = 'default')
    {
        $ems = \app\common\model\Ems::
        where(['email' => $email, 'event' => $event])
            ->order('id', 'DESC')
            ->find();
        Hook::listen('ems_get', $ems, null, true);
        return $ems ? $ems : null;
    }

    /**
     * 发送验证码
     *
     * @param   int    $email 邮箱
     * @param   int    $code  验证码,为空时将自动生成4位数字
     * @param   string $event 事件
     * @return  boolean
     */
      public static function send($receiver, $code = null, $event = 'default')
    {
        $code = is_null($code) ? mt_rand(1000, 9999) : $code;
        $time = time();
        $ip = request()->ip();
        $ems = \app\common\model\Ems::create(['event' => $event, 'email' => $receiver, 'code' => $code, 'ip' => $ip, 'createtime' => $time]);
         $email = new Email;
            $result = $email
                ->to($receiver)
                ->subject(__("优美汇验证码邮件"))
                ->message('优美汇提醒您!您的验证码是：【' .$code. '】')
                ->send();
        
            if ($result) {
                Cache::set($receiver,$code,600);
                 return true;
                
            } else {
                $ems->delete();
            return false;
            }
        

    }

    /**
     * 发送通知
     *
     * @param   mixed  $email    邮箱,多个以,分隔
     * @param   string $msg      消息内容
     * @param   string $template 消息模板
     * @return  boolean
     */
    public static function notice($email, $msg = '', $template = null)
    {
        $params = [
            'email'    => $email,
            'msg'      => $msg,
            'template' => $template
        ];
        $result = Hook::listen('ems_notice', $params, null, true);
        return $result ? true : false;
    }

    /**
     * 校验验证码
     *
     * @param   int    $email 邮箱
     * @param   int    $code  验证码
     * @param   string $event 事件
     * @return  boolean
     */
    public static function check($email, $code, $event = 'default')
    {
        $time = time() - self::$expire;
        $ems = \app\common\model\Ems::where(['email' => $email, 'event' => $event])
            ->order('id', 'DESC')
            ->find();
        if ($ems) {
            if ($ems['createtime'] > $time && $ems['times'] <= self::$maxCheckNums) {
                $correct = $code == $ems['code'];
                if (!$correct) {
                    $ems->times = $ems->times + 1;
                    $ems->save();
                    return false;
                } else {
                    $result = Hook::listen('ems_check', $ems, null, true);
                    return true;
                }
            } else {
                // 过期则清空该邮箱验证码
                self::flush($email, $event);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 清空指定邮箱验证码
     *
     * @param   int    $email 邮箱
     * @param   string $event 事件
     * @return  boolean
     */
    public static function flush($email, $event = 'default')
    {
        \app\common\model\Ems::
        where(['email' => $email, 'event' => $event])
            ->delete();
        Hook::listen('ems_flush');
        return true;
    }
}

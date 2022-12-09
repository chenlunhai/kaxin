<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\Money;
use app\common\model\MoneyLog;
use think\Db;

/**
 * 小工具接口
 */
class Tools extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * 冒泡排序
     * @param $arr
     * @param string $type
     */
    public static function orderArray($arr, $type = '<')
    {
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i + 1; $j < count($arr); $j++) {
                if ($type == '<') {
                    if ($arr[$i] < $arr[$j]) {
                        $tem     = $arr[$i]; // 这里临时变量，存贮$i的值
                        $arr[$i] = $arr[$j]; // 第一次更换位置
                        $arr[$j] = $tem; // 完成位置互换
                    }
                } else {
                    if ($arr[$i] > $arr[$j]) {
                        $tem     = $arr[$i]; // 这里临时变量，存贮$i的值
                        $arr[$i] = $arr[$j]; // 第一次更换位置
                        $arr[$j] = $tem; // 完成位置互换
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 获取配置表中的数据
     * @param $key
     * @param $type false 原样返回 json JSOn格式返回
     * @param $switch false 忽略启用状态 true 不忽略启用状态
     * @param $value string 需要的字段
     */
    public static function getConfig1($key, $type = false, $switch = false, $value = 'value')
    {
        if (strpos($key, ',')) {
            $key = explode(',', $key);
            $res = [];
            foreach ($key as $k => $v) {
                $where = "`key`='{$v}'";
                if (strpos($v, '_img')) {
                    $value   = 'image';
                    $res[$v] = Db::name('Zconfig')->where('key', $v)->value('image');
                }
                if ($switch) {
                    $where .= " and static='1'";
                }
                $res[$v] = Db::name('Zconfig')->where($where)->value($value);
                if ($res[$v] == null) {
                    if ($switch) {
                        return '';
                    } else {
                        $Api = new self();
                        $Api->error('配置有误' . $v);
                    }
                }
            }
            if ($type == 'json') {
                foreach ($res as $k => $v) {
                    $res[$k] = json_decode($v, true);
                }
            }
            return $res;
        } else {
            $where = "`key`='{$key}'";
            if (strpos($key, '_img')) {
                $value = 'image';
            }
            if ($switch) {
                $where .= " and static='1'";
            }
            $res = Db::name('Zconfig')->where($where)->value($value);

            if ($res == null) {
                if ($switch) {
                    return '';
                } else {
                    $Api = new self();
                    $Api->error('配置有误' . $key);
                }
            }
            if ($type == 'json') {
                return json_decode($res, true);
            } else {
                return $res;
            }
        }
    }

    /**
     * 升级用户等级
     * @param $uid int 用户ID
     * @param $class int 要升到的等级
     */
    public static function upUserClass($uid, $class)
    {
        $user = Db::name('zuser')->where('id=' . $uid)->field('zuser_ids,user_class,zuser_id')->find();
        $uids = $user['zuser_ids'];
        if ($user['user_class'] >= $class) {
            return true;
        }
        Db::name('zuser')->where("id = '{$uid}'")->update(['user_class' => $class]);
        switch ($class) {
            case 2://VIP
                Db::name('zuser')->where("id in ($uids)")->setInc('team_vip', 1);//增加团队VIp人数
                Db::name('zuser')->where("id = '{$user['zuser_id']}'")->setInc('direct_vip', 1);//增加直推VIp人数

                self::autoUpUserClass(3);
                break;
            case 3://县代
                Db::name('zuser')->where("id in ($uids)")->setInc('team_county', 1);//增加团队县代人数
                Db::name('zuser')->where("id = '{$user['zuser_id']}'")->setInc('direct_county', 1);//增加直推县代人数
                self::autoUpUserClass(4);
                break;
            case 4://市代
                Db::name('zuser')->where("id in ($uids)")->setInc('team_city', 1);//增加团队市代人数
                Db::name('zuser')->where("id = '{$user['zuser_id']}'")->setInc('direct_city', 1);//增加直推市代人数
                self::autoUpUserClass(5, $uids);
                break;
            case 5://省代
                Db::name('zuser')->where("id in ($uids)")->setInc('team_province', 1);//增加团队省代人数
                Db::name('zuser')->where("id = '{$user['zuser_id']}'")->setInc('direct_province', 1);//增加直推省代人数
                break;
        }
    }

    /**
     * 自动检索升级用户
     * @param int $class
     * @param string $uids
     */
    public static function autoUpUserClass($class = 3, $uids = '')
    {
        if ($class == 3) {
            $list = Db::name('zuser')->where('(direct_vip>=10 and user_class<' . $class . ') or (buy_vip_goods>=10 and user_class<' . $class . ')')->column('id');
            foreach ($list as $id) {//升级满足条件的县代
                self::upUserClass($id, $class);
            }
        } elseif ($class == 4) {
            $list = Db::name('zuser')->where('(team_vip>=300 and user_class<' . $class . ') or (buy_vip_goods>=50 and user_class<' . $class . ')')->column('id,buy_vip_goods');
            foreach ($list as $id => $buy_vip_goods) {
                if ($buy_vip_goods >= 50) {
                    self::upUserClass($id, $class);
                } else {
                    $have = Db::name('zuser')->where('zuser_id=:id and team_county>=1', ['id' => $id])->value('id');
                    if (count($have) >= 3) {
                        self::upUserClass($id, $class);
                    }
                }
            }
        } elseif ($class == 5) {
            $uids = explode(',', $uids);
            foreach ($uids as $id) {
                $have = Db::name('zuser')->where('zuser_id=:id and team_city>=1', ['id' => $id])->value('id');
                if (count($have) >= 3) {
                    self::upUserClass($id, $class);
                }
            }
        }
    }

    //根据数字获取用户等级文本
    public static function getUserClassTextByNum($num)
    {
        $user_class_rule = self::getConfig1('user_class_rule', true);
        return $user_class_rule[$num];
    }

    /**
     * 得到post中的数据
     * @param bool $needArr
     * @return mixed
     */
    public static function getPost1($needArr = false)
    {
        $post = input('post.');
        foreach ($post as $k => $v) {
            is_array($v) || $post[$k] = trim($v);
        }
        if ($needArr !== false) {
            $field = self::needField1($post, $needArr);
            if ($field !== true) {
                $Api = new Api();
                $Api->error($field . '不能为空');
            }
        }
        return $post;
    }

    /**
     * 判断一个数组中是否存在必须的字段
     * @param $arr
     * @param $fieldArr
     */
    public static function needField1($arr, $fieldArr)
    {
        foreach ($fieldArr as $k => $v) {
            if (!isset($arr[$v])) {
                return $v;
            }
        }
        return true;
    }

    /**
     * @param string $str 要验证的字符串
     * @param string $type 要验证的类型 mobile pwd token incode num(纯数字) email idcard funum负数
     */
    public static function checkDiy1($str, $type = 'mobile')
    {
        $pattern = [
            'mobile'     => "/^1[3-9]{1}\d{9}$/isU",
            'pwd'        => "/^[\w.!@#$]{6,16}$/isU",
            'token'      => "/^[a-z0-9]{32}$/isU",
            'incode'     => "/^[A-Z0-9]{6}$/isU",
            'num'        => "/^\d+$/isU",
            'funum'      => "/^-?\d+$/isU",
            'email'      => "/^([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?$/i",
            'idcard'     => '/^\d{17}[0-9x]{1}$/i',
            'time'       => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/i',
            'money'      => '/^\d{1,}.?\d*$/i',
            'order_sn'   => '/^(alipay_|in_){1}\d{20,}$/isU',
            'usdt_order' => '/^0x[0-9a-f]{64}$/isU',
            'china_char' => '/^[0-9a-z\x{4e00}-\x{9fa5}]+$/isu',
        ];
        if (isset($pattern[$type])) {
            if (preg_match($pattern[$type], $str)) {
                return true;
            }
        } else {
            if (preg_match($type, $str)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 激活用户操作
     * @param $uid
     */
    public static function activationUser($uid)
    {
        $User = Db::name('Zuser')->where('id=' . $uid);
        $res  = $User->update(['activation' => '1']);
        if ($res) {
            $zuser_id = $User->value('zuser_id');
            if ($zuser_id) {
                $resArr[] = Db::name('Zuser')->where('id=' . $zuser_id)->update(['activation_num' => Db::raw('activation_num+1')]);
            }
        }
    }
}

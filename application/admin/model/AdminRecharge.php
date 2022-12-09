<?php

namespace app\admin\model;

use think\Model;


class AdminRecharge extends Model
{

    

    

    // 表名
    protected $name = 'admin_recharge';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'dojog_text',
        'ctype_text'
    ];
    

    
    public function getDojogList()
    {
        return ['add' => __('Add'), 'reduce' => __('减少')];
    }

    public function getCtypeList()
    {
        return ['money' => __('Money'), 'balance' => __('积分'), 'score' => __('消费积分')];
    }


    public function getDojogTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['dojog']) ? $data['dojog'] : '');
        $list = $this->getDojogList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCtypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctype']) ? $data['ctype'] : '');
        $list = $this->getCtypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

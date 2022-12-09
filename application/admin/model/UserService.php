<?php

namespace app\admin\model;

use think\Model;


class UserService extends Model
{

    

    

    // 表名
    protected $name = 'user_service';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'flag_text'
    ];
    

    
    public function getFlagList()
    {
        return ['pass' => __('Pass'), 'refuse' => __('Refuse'), 'ing' => __('Ing')];
    }


    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['flag']) ? $data['flag'] : '');
        $list = $this->getFlagList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function user()
    {
        return $this->belongsTo('User', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

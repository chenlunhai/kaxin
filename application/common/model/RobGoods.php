<?php

namespace app\common\model;

use think\Model;


class RobGoods extends Model
{

    

    

    // 表名
    protected $name = 'rob_goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_delete_text'
    ];
    

    
    public function getIsDeleteList()
    {
        return ['yes' => __('Yes'), 'no' => __('No')];
    }


    public function getIsDeleteTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_delete']) ? $data['is_delete'] : '');
        $list = $this->getIsDeleteList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function wanlshopgoods()
    {
        return $this->belongsTo('WanlshopGoods', 'gid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

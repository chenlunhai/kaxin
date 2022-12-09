<?php

namespace app\common\model;

use think\Model;


class Zconfig extends Model
{

    

    

    // 表名
    protected $name = 'zconfig';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'static_text'
    ];
    

    
    public function getStaticList()
    {
        return ['0' => __('Static 0'), '1' => __('Static 1')];
    }


    public function getStaticTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['static']) ? $data['static'] : '');
        $list = $this->getStaticList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

<?php

namespace app\admin\model;

use think\Model;


class SystemConfig extends Model
{

    

    

    // 表名
    protected $name = 'system_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'switch_text'
    ];
    

    
    public function getSwitchList()
    {
        return ['Yes' => __('Yes'), 'No' => __('No')];
    }


    public function getSwitchTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['switch']) ? $data['switch'] : '');
        $list = $this->getSwitchList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

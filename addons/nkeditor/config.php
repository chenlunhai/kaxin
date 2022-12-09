<?php

return [
    [
        //配置唯一标识
        'name'    => 'theme',
        //显示的标题
        'title'   => '编辑器主题',
        //类型
        'type'    => 'select',
        //数据字典
        'content' => [
            'default' => '经典主题',
            'black'   => '雅黑主题',
            'blue'    => '淡蓝主题',
            'grey'    => '深灰主题',
            'primary' => '深绿主题',
        ],
        //值
        'value'   => 'black',
        //验证规则 
        'rule'    => 'required',
        //错误消息
        'msg'     => '',
        //提示消息
        'tip'     => '',
        //成功消息
        'ok'      => '',
        //扩展信息
        'extend'  => ''
    ],
    [
        'name'    => 'wordimageserver',
        'title'   => '启用word图片替换服务器',
        'type'    => 'radio',
        'content' => [
            '1' => '是',
            '0' => '否',
        ],
        'value'   => '0',
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '如果启用，请务必先运行word.exe',
        'ok'      => '',
        'extend'  => ''
    ],
    [
        'name'    => 'attachmentmode_admin',
        'title'   => '管理员附件选择模式',
        'type'    => 'select',
        'content' => [
            'all'      => '任何管理员均可以查看全部上传的文件',
            'auth'     => '仅可以查看自己及所有子管理员上传的文件',
            'personal' => '仅可以查看选择自己上传的文件',
        ],
        'value'   => 'all',
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => ''
    ],
    [
        'name'    => 'attachmentmode_index',
        'title'   => '后台附件选择模式',
        'type'    => 'select',
        'content' => [
            'all'      => '任何会员均可以查看全部上传的文件',
            'personal' => '仅可以查看选择自己上传的文件',
        ],
        'value'   => 'all',
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => ''
    ],
];

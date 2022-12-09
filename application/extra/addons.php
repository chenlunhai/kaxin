<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'alioss',
            'epay',
            'wanlshop',
        ],
        'upload_config_init' => [
            'alioss',
        ],
        'upload_delete' => [
            'alioss',
        ],
        'sms_send' => [
            'alisms',
        ],
        'sms_notice' => [
            'alisms',
        ],
        'sms_check' => [
            'alisms',
        ],
        'leesignhook' => [
            'leesign',
        ],
        'config_init' => [
            'nkeditor',
        ],
        'user_sidenav_after' => [
            'signin',
            'wanlshop',
        ],
        'upgrade' => [
            'wanlshop',
        ],
    ],
    'route' => [
        '/example$' => 'example/index/index',
        '/example/d/[:name]' => 'example/demo/index',
        '/example/d1/[:name]' => 'example/demo/demo1',
        '/example/d2/[:name]' => 'example/demo/demo2',
        '/leesign$' => 'leesign/index/index',
    ],
    'priority' => [],
];

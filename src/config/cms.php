<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMS引擎配置
    |--------------------------------------------------------------------------
    |
    */
    'config' => [
        'namespace' => 'eol',
        'tagstart' => '{',
        'tagend' => '}',
        'tagmaxlen' => 60,
        'tolow' => TRUE,
        'home' => storage_path('template'),
    ],

    'tags' => [
        'field' => [
//            'site' => classname
        ],
        'list' => [],
        'page' => [],
    ],

];


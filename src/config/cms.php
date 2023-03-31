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
        'disk' => 'local',
        'include_deep' => 3, // 默认3级
    ],

    'tags' => [
//        'site' => ['type' => 'field', 'target' => 'cms.field'],
//        'arclist' => ['type' => 'list', 'target' => 'cms.list'],
//        'list' => ['type' => 'page', 'target' => 'cms.page'],
    ],

];


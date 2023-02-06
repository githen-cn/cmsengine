<?php

namespace Githen\CmsEngine;

// 自动注册为服务
use App\Extend\Encrypter;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CmsProvider extends ServiceProvider
{
    /**
     * 服务注册
     *
     * @return void
     */
    public function register()
    {
        // 发布配置文件
        $this->updateFile();
    }

    /**
     * 启动服务
     *
     * @return void
     */
    public function boot()
    {
        // 注册模板解析服务
        $this->app->singleton('html.tpl', function ($app, $d){
            return new HtmlPrase($this->app);
        });
    }


    /**
     * 更新需要的文件
     *
     * @param 强制更新
     * @return void
     */
    private function updateFile()
    {
        $this->publishes([__DIR__.'/config/cms.php' => config_path('cms.php')]);
    }

}

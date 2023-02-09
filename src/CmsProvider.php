<?php
namespace Githen\CmsEngine;

use Illuminate\Support\ServiceProvider;

/**
 * 自动注册为服务
 */
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
        $this->app->singleton('html.tpl', function (){
            return new HtmlParse($this->app);
        });
    }

    /**
     * 更新需要的文件
     *
     * @return void
     */
    private function updateFile()
    {
        $this->publishes([__DIR__.'/config/cms.php' => config_path('cms.php')]);
    }

}

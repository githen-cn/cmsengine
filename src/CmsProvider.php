<?php

namespace Githen\CmsEngine;

// 自动注册为服务
use App\Extend\Encrypter;
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
        $this->updateFile(true);

        // 注册服务
        $this->app->singleton('cms', function ($app) {
            // 获取配置
            $config = $app->make('config')->get('cms.config', []);

            //初始化实例
            return new HtmlPrase($config);
        });
        //
    }

    /**
     * 启动服务
     *
     * @return void
     */
    public function boot()
    {
//        dd($this->app->cms);


    }

    /**
     * 更新需要的文件
     *
     * @param 强制更新
     * @return void
     */
    private function updateFile($isForce = false)
    {
        if (!is_file(config_path('cms.php')) || $isForce)
        {
            copy(__DIR__.'/config/cms.php', config_path('cms.php'));
        }
    }

}

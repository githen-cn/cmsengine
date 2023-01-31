
# CMS模板生成扩展

这是一个基于laravel框架，为CMS模板引擎生成HTML实现的扩展包。
支持对属性值，列表自定义标签。


## 安装

```shell
composer require githen/cmsengine

# 迁移配置文件
php artisan vendor:publish --provider="Githen\CmsEngine\CmsProvider"
```

## 配置文件说明
```php
return [
    // 基础配置
    'config' => [
        'namespace' => 'eol',  // 标记名
        'tagstart' => '{',     // 标记开始符
        'tagend' => '}',       // 标记结束符
        'tagmaxlen' => 60,     // 标签名最长长度
        'tolow' => TRUE,       // 标签大小写不敏感
        'home' => storage_path('template'), // 模板根目录
    ],

    // 标签白名单，只有在此定义才可解析通过
    // type 目前有三种  field(属性值)，list(列表), page(分页)
    // taget 实现类，通过 app('cms.field') 方式调用出来
    'tags' => [
        'site' => ['type' => 'field', 'taget' => 'cms.field'],  
        'school' => ['type' => 'field', 'taget' => 'cms.field'],
        'arclist' => ['type' => 'list', 'taget' => 'cms.list'],
        'page' => ['type' => 'page', 'taget' => 'cms.field'],
    ],

];
```

## 标签说明

目前标签解析支持三种方式：
1. 获取属性值
 > {eol:tag.title/}
 >
 > {eol:tag name="title}
2. 对列表进行渲染
 > {eol:tag id=1 order='desc'}
 > 
 >  <li>[field:title]</li>
 > 
 >  {/eol:tag}

## 使用举例

### 项目中调用

```php
$tpl = clone app('html.tpl');
$tpl->setNameSpace('eol', '{', '}')->loadTemplate('index.html');

// 获取渲染后的html
$html = $tpl->fetch();

// 渲染后的html直接保存到文件()
// 需要在config/filesystems.php中声明 local 的驱动
$tpl->saveTo('index.html');
```

### 注册自定义数据服务提供者

```php

// 数据获取服务提供者，自我声明服务提供者，并在config/app.php中加载

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 注册服务方式
        $this->app->singleton('cms.field', function ($app) {
            return new Fields();
        });
        $this->app->singleton('cms.list', function ($app) {
            return new Lists();
        });
    }

```

### 取数据值实例
```php
class Fields
{
    /**
     * 缓存数据
     * [
     *      $site_id => [
     *          'site' => Model
     *      ]
     * ]
     */
    private $cache;

    /**
     * @param $tag
     * @param $linkData
     */
    public function data($tag, $linkData)
    {
        // 获取标签指定数据
        return $this->{$tag->tagName}($tag, $linkData);
    }

    /**
     * 获取值数据，直接填充
     */
    private function site($tag, $linkData)
    {
        // 获取站点缓存类
        $cacheKey = $linkData['site_id'].'.site';
        if(! $site = data_get($this->cache, $cacheKey, false)){
            $site = Site::select('name','school_id', 'domain', 'title', 'keywords', 'description')->where('id', $linkData['site_id'])->first();
            data_set($this->cache, $cacheKey, $site);
            dump("查询了下site:".$linkData['site_id']);
        }

        return $site->{$tag->getAttribute('name')};
    }
}


```

### 获取列表实例
```php

class Lists
{
    /**
     * 缓存数据
     * [
     *      $site_id => [
     *          'site' => Model
     *      ]
     * ]
     */
    private $cache;

    /**
     * @param $tag
     * @param $linkData
     */
    public function data($tag, $linkData)
    {
        // 获取标签指定数据
        return $this->{$tag->tagName}($tag, $linkData);
    }

    /**
     * 获取列表数据
     */
    private function arclist($tag, $linkData)
    {
        // 必要参数检测

        // code ...

        // 获取站点缓存类
        $unikey = "{$tag->getAttribute('id')}_{$tag->getAttribute('row')}";
        $cacheKey = $linkData['site_id'].'.arclist.'.$unikey;
        if(! $arclist = data_get($this->cache, $cacheKey, false)){
            $arclist = News::select('type','title', 'cover', 'content', 'created_at', 'description')->where('category_id', $tag->getAttribute('id'))->limit($tag->getAttribute('row'))->get();
            data_set($this->cache, $cacheKey, $arclist);
            dump("查询了下栏目id为".$tag->getAttribute('id').'下面的文章，site_news');
        }

        return $arclist->toArray();
    }
}
```

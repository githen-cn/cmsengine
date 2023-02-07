
# CMS模板生成扩展

这是一个基于laravel框架，为CMS模板引擎生成HTML实现的扩展包。
支持对属性值，列表自定义标签。

<a href='https://gitee.com/githen/cmsengine/stargazers'><img src='https://gitee.com/githen/cmsengine/badge/star.svg?theme=dark' alt='star'></img></a>
<a href='https://gitee.com/githen/cmsengine/members'><img src='https://gitee.com/githen/cmsengine/badge/fork.svg?theme=dark' alt='fork'></img></a>

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
        'page' => ['type' => 'page', 'taget' => 'cms.page'],
    ],

];
```

## 标签说明

目前标签解析支持三种方式：
1. 获取属性值
> {eol:tag.title/}
> 
> {eol:tag name="title"/}

2. 对列表/分页进行渲染
> {eol:arclist id=1 row=60 order='desc'}
> 
>  &lt;li&gt;[field:title]&lt;/li&gt;
> 
>  {/eol:tag}
> 

3. 内置标签
* include

> 标签举例
> 
> {eol:include filename='footer.html'/}
> 
> 引擎将会把 app('html.tpl')中 $homeDir/footer.html进行渲染生成html替换此标签
> 


* global
> 全局定义的属性
> 
> {eol:global.page_total/}  // 总条数
> 
> {eol:global.page_size/}   // 每页数据
> 
> {eol:global.page_num/}    // 总页数
> 
> {eol:global.page_index/}  // 当前页数
> 
> {eol:global.page_url/}  // 分页规则
>
> {eol:global.domain/}  // 静态资源域名
> 
> {eol:global.tplid/}   //  模板id
> 
> {eol:global.resource_url/}  // 静态资源调用地址
> 
> 注：此地址是将domain和tplid进行组装，若tplid未设置或为0，则不拼接
> 
> $domain = 'https://www.test.com';
> 
> $tplid = 0
> 
> 则$resource_url 为 'https://www.test.com/'  
> 
>若$tplid = 10 或 $tplid = 'addd'
> 
> 则$resource_url 为 'https://www.test.com/10/' 或 'https://www.test.com/addd/'


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

//分页调用,直接返回需要页面的html代码
$html = $tpl->fetch(2);

// 遍历生成文件, {page}将会生成对应的页码数，如果存在第二个参数，则首页将被重命名为此参数
$tpl->saveTo('aa/list{page}.html', 'new.html');

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
        
        $this->app->singleton('cms.page', function ($app) {
            return new Pages();
        });
    }

```

### 取数据值实例
在`config/cms.php`中的`tags`中，声明的标签`type`为`field`时，会自动调用此类，标签名为类中的方法名。
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
在`config/cms.php`中的`tags`中，声明的标签`type`为`list`时，会自动调用此类，标签名为类中的方法名。
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
     *  
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

### 获取分页实例
在`config/cms.php`中的`tags`中，声明的标签`type`为`page`时，会自动调用此类，标签名为类中的方法名。
分页中若展示页码，可通过`global`获取分页信息，通过`js`渲染
```php
<?php

namespace App\Extend\Cmss;

use App\Models\School;
use App\Models\Site;

class Pages
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
    private function list($tag, $linkData)
    {
        // 必要参数检测

        // code ...
        // 栏目ID是否填写，学校id，站点id是否有权限等

        // 需要生成分页的第几页
        $page = $linkData['page_index'];

        // 获取站点缓存类
        $unikey = "{$tag->getAttribute('size')}_{$tag->getAttribute('per')}_{$page}";
        $cacheKey = $linkData['site_id'].'.list.'.$unikey;
        if(! $data = data_get($this->cache, $cacheKey, false)){
            // 获取总条数
            $count = News::where('category_id', $linkData['category_id'])->count();
            $arclist = News::select('type','title', 'cover', 'content', 'created_at', 'description')
                ->where('category_id', $linkData['category_id'])
                ->skip(($page-1) * $tag->getAttribute('per'))
                ->take($tag->getAttribute('per'))->get();

            $data = [
                'total' => (int)$count,  // 总条数
                'size' => (int)$tag->getAttribute('per'), // 每页
                'items' => $arclist->toArray() // 当前页列表数据
            ];

            data_set($this->cache, $cacheKey, $arclist);
            dump("查询了下栏目id为".$linkData['category_id'].'下面的文章分页，page：'.$page);
        }

        return $data;

    }
}

```

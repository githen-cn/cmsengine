<?php

namespace Githen\CmsEngine\Lib;

use Githen\CmsEngine\Exceptions\HtmlParseException;
use Githen\CmsEngine\HtmlParse;

trait TagTrait {
    /**
     * 静态资源域名
     * @val string
     */
    private $domain = '';
    /**
     * 模板id
     * @val string
     */
    private $tplid = '';

    /**
     * 内置标签
     */
    private $inTags = [
        'include',  // 文件包含
        'global',   // 全局属性标签
        'foreach',  // 遍历
    ];

    /**
     * include标签深度
     * @var array
     */
    private $includeInfo = [];



    /**
     * 设置静态资源域名
     */
    public function setDomain($domain): HtmlParse
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * 设置模板id
     */
    public function setTplid($tplid): HtmlParse
    {
        $this->tplid = $tplid;
        return $this;
    }

    /**
     * include 标签处理
     * @param $tag
     * @return HtmlParse
     * @throws HtmlParseException
     */
    public function tagInclude($tag): HtmlParse
    {
        // 获取要引入的文件
        if(! $fileName = $tag->getAttribute('filename')){
            throw new HtmlParseException('标签 "'.$tag->tagName.'"参数filename不存在 ('.$this->position($tag->posStart) . ')！');
        }

        $fullPath = $this->homeDir .'/'. $fileName;
        if (!is_file($fullPath)) {
            $tag->assign('标签：include包含的文件（'.$fileName.'）不存在');
            return $this;
        }

        // 替换为html
        $tpl = clone $this;

        // 累加深度值
        $tpl->includeAppend($fileName);
        if ($tpl->includeCount() > $this->includeDeep){
            $tag->assign('标签：include引用超过'.$this->includeDeep.'级');
            return $this;
        }

        $tag->assign($tpl->clear()->loadTemplate($fileName)->fetch());
        unset($tpl);

        return $this;
    }

    /**
     * 追加统计信息
     * @param $fileName 加载文件名
     * @return $this
     */
    public function includeAppend($fileName)
    {
        // 首次加载
        if (!$this->includeInfo){
            $this->includeInfo = ['count' => 1, 'track' => [$fileName]];
            return $this;
        }

        //累加计数
        $this->includeInfo['count']++;
        $this->includeInfo['track'][] = $fileName;
        return $this;
    }

    /**
     * 返回深度
     * @return int|mixed
     */
    public function includeCount()
    {
        return $this->includeInfo['count']??0;
    }

    /**
     * 清除统计信息
     * @return $this
     */
    public function includeClean()
    {
        $this->includeInfo = [];
        return $this;
    }

    /**
     * global 标签处理
     * @param $tag
     * @return HtmlParse
     * @throws HtmlParseException
     */
    public function tagGlobal($tag): HtmlParse
    {

        switch ($tag->getAttribute('name')){
            case 'domain':
            case 'tplid':
                $val = $this->{$tag->getAttribute('name')};
                break;

            case 'resource_url':
                $val  = rtrim($this->domain, '/') . '/';
                if ($this->tplid){
                    $val .= $this->tplid . '/';
                }
                break;

            case 'page_total':
            case 'page_size':
            case 'page_num':
            case 'page_index':
            case 'page_url':
                $val = $this->pageInfo[$tag->getAttribute('name')];
                break;
            case 'build_time':
                $val = date('Y-m-d H:i:s');
                break;
            default:
                throw new HtmlParseException('标签 "'.$tag->tagName.'"属性值"'.$tag->getAttribute('name').'"不存在 ('.$this->position($tag->posStart) . ')！');
        }

        $tag->assign($val);

        return $this;
    }

    /**
     * foreach 标签
     * @param Tag $tag
     * @param array $data 遍历的数组
     * @return string
     * @throws HtmlParseException
     */
    public function tagForeach(Tag $tag, array $data = []): string
    {
        $tpl = clone $this;
        $tpl->clear()->setNameSpace('field', '[', ']', false)->loadSource($tag->innerText);

        $html = '';
        foreach ($data as $key => $val){
            foreach ($tpl->getTags() as $tmpTag){
                // 数组中有此索引值，优先使用，无则对 key,val进行赋值
                $tmpVal = $val[$tmpTag->tagName] ?? ${$tmpTag->tagName};

                if ($functionName = $tmpTag->getAttribute('function')){
                    $tmpVal = $this->getFunctions($functionName, $tmpVal);
                }

                $tmpTag->assign($tmpVal, [], false);
            }
            $html .= $tpl->fetch();
        }

        return $html;
    }
}

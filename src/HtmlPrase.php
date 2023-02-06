<?php

namespace Githen\CmsEngine;


use Githen\CmsEngine\Exceptions\HtmlPraseException;
use Githen\CmsEngine\Tag;
use Illuminate\Support\Facades\Storage;
use function GuzzleHttp\Psr7\str;

/**
 * HTML模板解析引擎
 *
 */
class HtmlPrase
{
    /**
     * @var object
     */
    private $app;

    /**
     * 模板文件目录
     * @var stirng
     */
    private $homeDir = '';

    /**
     * 标记名称
     * @var string
     */
    private $nameSpace = '';

    /**
     * 标记开始字符
     * @var string
     */
    private $tagStart = '';

    /**
     * 标记结束字符
     * @var string
     */
    private $tagEnd = '';
    /**
     * 标记是否检测
     * @var bool
     */
    private $isCheck = true;

    /**
     * 标签最大长度
     * @var string
     */
    private $tagMaxLen = '';

    /**
     * 标答及属性名称是否区分大小写
     *
     * @var bool
     */
    private $toLow = true;

    /**
     * 执行时间
     *
     * @val int
     */
    private $makeTime = 0;

    /**
     * 模板原始数据
     *
     * @val string
     */
    private $sourceHtml = '';

    /**
     * 标签集合
     *
     * @val array
     */
    private $tags = [];

    /**
     * 标签个数
     *
     * @val int
     */
    private $tagNum = 0;

    /**
     * 中转参数
     * @val array
     */
    private $linkData = [];

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
     * 分页数据
     * @val array
     */
    private $pageInfo = [
        'page_total' => 0,  // 总条数
        'page_size' => 0, // 每页系数
        'page_num' => 0,  // 总页数
        'page_index' => 0,  // 当前第几页
        'page_url' => '',  // 分页规则

    ];

    /**
     * 内置标签
     */
    private $inTags = [
        'include',  // 文件包含
        'global' // 全局属性标签
    ];

    /**
     * 构建引擎所需要的配置信息
     *
     * @param array
     *
     * @return void
     */
    function __construct($app)
    {
        $this->app = $app;

        // 加载配置
        $config = $this->app->make('config')->get('cms.config', []);
        $this->setConfigs($config);
    }

    /**
     * 修改默认配置
     *
     * @param array
     *
     * @return void
    */
    public function setConfigs($options = [])
    {
        // 设置标记名称
        $this->setNameSpace(
            $options['namespace']??'EOL',
            $options['tagstart']??'{',
            $options['tagend']??'}'
        );

        // 设置模板目录
        if (isset($options['home'])){
            $this->setHomedir($options['home']);
        }

        $this->tagMaxLen = $options['tagmaxlen'] ?? 60;
        $this->toLow     = $options['tolow']     ?? TRUE;

        return $this;
    }

    /**
     * 设置静态资源域名
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * 设置分页规则
     *
     */
    public function setPageRule($rule)
    {
        $this->pageInfo['page_url'] = $rule;
        return $this;
    }

    /**
     * 设置模板id
     */
    public function setTplid($tplid)
    {
        $this->tplid = $tplid;
        return $this;
    }

    /**
     * 设置标记名称
     *
     * @param string $name  标记名称
     * @param string $start 标记开始字符
     * @param string $end   标记结束字符
     * @param bool   $isCheck 标签检测
     *
     * @return void
     */
    public function setNameSpace($name, $strat = '{', $end = '}', $isCheck= true)
    {
        $this->nameSpace = $name;
        $this->tagStart  = $strat;
        $this->tagEnd    = $end;
        $this->isCheck   = $isCheck;

        return $this;

    }

    /**
     * 设置模板目录
     *
     */
    public function setHomedir($dir)
    {
        $this->homeDir = $dir;
        return $this;
    }

    /**
     * 设置连接数据
     *
     */
    public function setLinkData($data)
    {
        $this->linkData = $data;
        return $this;
    }

    /**
     * 获取连接数据
     *
     */
    public function getLinkData()
    {
        return $this->linkData;
    }

    /**
     * 重置解析数据
     *
     *
     */
    public function clear()
    {
        $this->sourceHtml = '';
        $this->tags = [];
        $this->tagNum = 0;

        return $this;
    }

    /**
     * 获取所有解析的标签
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * 检测禁用函数
     */
    public function checkDisabledFunctions()
    {

    }
    /**
     * 检测禁用标签
     */
    public function checkDisabledTags()
    {

    }

    /**
     * 加载模板文件
     *
     * @param string $filename 文件名称
     *
     * @return void
     */
    public function loadTemplate($filename)
    {
        // 清除旧数据
        $this->clear();

        $path = $this->homeDir . '/' . $filename;
        if (!file_exists($path)){
            $this->sourceHtml = $filename.' 文件不存在';
            return;
        }

        return $this->loadSource(file_get_contents($path));
    }

    /**
     * 加载模板字符串
     *
     * @param string $string 需要解析的字符串
     *
     * @return void
     */
    public function loadSource($string)
    {
        $this->sourceHtml = $string;
        return $this->parseTemplate();
    }

    /**
     * 解析模板文件
     */
    public function parseTemplate()
    {
        // 开始标签 {标识:
        $tagStartAll = $this->tagStart . $this->nameSpace . ':';
        $tagStartAllLen = strlen($tagStartAll);

        // 结束标识 {/标识：
        $tagEndAll = $this->tagStart . '/' . $this->nameSpace.':';

        // 结束标识 /}
        $tagEnd = '/' . $this->tagEnd;

        $sourceLen = strlen($this->sourceHtml);

        // 长度过短，不执行解析
        if ($sourceLen <= $tagStartAllLen + 3){
            return;
        }

        // 标签处理截断标识
        $clipChar = ["/", "\r", "\t", "\n", " ", $this->tagEnd];

        // 加载配置信息
        $tags = $this->app->make('config')->get('cms.tags', []);

        // 执行解析
        for ($i = 0; $i < $sourceLen; $i++){
            // 当前匹配的标签名称
            $tmpTagName = '';

            $posStart = $i-1;
            if ($posStart < 0 ) $posStart = 0;

            // 查找自定位标签位置
            $posCur = strpos($this->sourceHtml, $tagStartAll, $posStart);

            // 未查询到有效标签，退出
            if ($posCur === false){
                break;
            }

            // 解析选中标签中的属性信息
            for ($j=0; $j<$this->tagMaxLen; $j++){
                $posTmp = $j + $posCur + $tagStartAllLen;

                // 累加超过最长长度 或 属于截断字符
                if ($posTmp > $sourceLen -1 || in_array($this->sourceHtml[$posTmp], $clipChar)) break;
                else $tmpTagName .= $this->sourceHtml[$posTmp];
            }

            // 模板名称为空
            if (! $tmpTagName){
                throw new HtmlPraseException('在位置'.$posCur . '未检测到标签名称！');
                break;
            }

            $i = $posCur + $tagStartAllLen;
            $len = 0;

            // 查找三种结束标签的结束位置
            $tagFull = $tagEndAll . $tmpTagName . $this->tagEnd;
            $pos1 = strpos($this->sourceHtml, $tagEnd, $i);      // /}
            $pos2 = strpos($this->sourceHtml, $tagStartAll, $i); // {EOL:
            $pos3 = strpos($this->sourceHtml, $tagFull, $i);     // {/EOL:x}

//            dump($tagEnd, $tagStartAll, $tagFull);

            // 没有匹配 {/EOL:x}  或 /} 比 {EOL: {/EOL:x}  位置更靠前
            if ($pos3 === false || ($pos1 < $pos2 && $pos1 < $pos3)){
                $posEnd = $pos1;
                $len = $posEnd + strlen($tagEnd);
            }else{
                // 没有匹配 /}
                $posEnd = $pos3;
                $len = $posEnd + strlen($tagFull);
            }
            if (! $len){
                throw new HtmlPraseException('标签 "'.$tmpTagName.'"错误（'.$this->position($posCur) . '）！');
            }

            $i = $len;

            // 对找到位置的标签进行分析
            $attribute = $innerText = '';
            $tmpType = "attribute";
            for ($j =  $posCur + $tagStartAllLen; $j < $posEnd; $j++){
                if ($tmpType == 'attribute' && $this->sourceHtml[$j] == $this->tagEnd && $this->sourceHtml[$j-1]!= '\\'){
                    $tmpType = 'innerText';
                    continue;
                }

                $$tmpType .= $this->sourceHtml[$j];
            }

            $tagObject = new Tag();
            $tagObject->setSource($attribute);

            if ($tagObject->tagName){
                // 检测标签是否支持
                if ($this->isCheck && !in_array($tagObject->tagName, $this->inTags) && !array_key_exists($tagObject->tagName, $tags)){
                    throw new HtmlPraseException('标签 "'.$tagObject->tagName.'"暂不支持 ('.$this->position($posCur) . ')！');
                }

                // 检测标签没有属性的情况
                if ($this->nameSpace != 'field' && count($tagObject->getAttributes()) == 0){
                    throw new HtmlPraseException('标签 "'.$tagObject->tagName.'"未填写属性值 ('.$this->position($posCur) . ')！');
                }

                $this->tagNum++;
                $tagObject->posStart = $posCur;
                $tagObject->posEnd = $i;
                $tagObject->innerText = $innerText;
                $this->tags[] = $tagObject;
            }
        }

        return $this;
    }

    /**
     * 位置转换
     */
    private function position(int $pos)
    {
        // 行数
        $lineNum = substr_count($this->sourceHtml, "\n", 0, $pos) + 1;

        // 当前行行几列
        $colNum = $pos - strrpos($this->sourceHtml, "\n", $pos - strlen($this->sourceHtml));

        return $lineNum.'行'.$colNum.'列';
    }


    /***********数据渲染***************/

    /**
     * 渲染生成页面
     * @param $page 当前页数
     */
    public function fetch()
    {
        // 未解析到属性
        if (! $this->tagNum) return $this->sourceHtml;

        // 加载配置信息
        $tags = $this->app->make('config')->get('cms.tags', []);

        // 对tag进行渲染
        $html = '';
        $nextPos = 0;
        $isPage = 0;
        foreach ($this->tags as $tag){

            // 内置标签处理
            if (in_array($tag->tagName, $this->inTags)){
                $this->{'tag'.ucwords($tag->tagName)}($tag);
            }

            // 检测是否已替换内容 并且 不为属性标签
            if (! $tag->isReplace && $this->nameSpace != 'field'){
                $tagConfig = $tags[$tag->tagName];

                // 数据获取类
                $tagObject = $this->app->make($tagConfig['target']);
                $data = $tagObject->data($tag, $this->getLinkData());

                // 分页数据，处理分页数据
                if ($tagConfig['type'] == 'page'){
                    $this->pageInfo['page_total'] = $data['total'];
                    $this->pageInfo['page_size'] = $data['size'];
                }

                // 解析列表数据
                if ($tagConfig['type'] == 'list' || $tagConfig['type'] == 'page'){
                    $tpl = clone $this;
                    $tpl->clear()->setNameSpace('field', '[', ']', false)->loadSource($tag->innerText);

                    // 数据遍历，渲染数据
                    $data = array_map(function ($item)use($tpl){
                        foreach ($tpl->getTags() as $tmpTag){
                            $tmpTag->assign($item[$tmpTag->tagName] ?? '');
                        }
                        return $tpl->fetch();
                    }, $tagConfig['type'] == 'page' ? $data['items']:$data);
                    $data = implode($data, "");
                    unset($tpl);
                }

                // 获取数据
                $tag->assign($data);
            }

            $html .= substr($this->sourceHtml, $nextPos, $tag->posStart-$nextPos);
            $html .= $tag->tagVal;
            $nextPos = $tag->posEnd;
        }

//        dd($html, 1111);

        $sourceLen = strlen($this->sourceHtml);
        if ($sourceLen > $nextPos){
            $html .= substr($this->sourceHtml, $nextPos, $sourceLen - $nextPos);
        }

        return $html;
    }

    /**
     * 保存内容到指定文件
     * @param string $file
     */
    public function saveTo($file)
    {
        $html = $this->fetch();
        return Storage::disk('local')->put($file, $html);
    }

    /**
     * include 标签处理
     * @return
     */
    public function tagInclude($tag)
    {
        // 获取要引入的文件
        if(! $fileName = $tag->getAttribute('filename')){
            throw new HtmlPraseException('标签 "'.$tag->tagName.'"参数filename不存在 ('.$this->position($tag->posStart) . ')！');
        }

        $fullPath = $this->homeDir .'/'. $fileName;
        if (!is_file($fullPath)) {
            $tag->assign('标签：include包含的文件（'.$fileName.'）不存在');
            return $this;
        }

        // 替换为html
        $tpl = clone $this;
        $tag->assign($tpl->clear()->loadTemplate($fileName)->fetch());
//        $tag->assign("<p>龙鱼_http://qmt.jiaoyu.cn");
        unset($tpl);

        return $this;
    }


    /**
     * global 标签处理
     * @return
     */
    public function tagGlobal($tag)
    {
        $val = '';
        switch ($tag->getAttribute('name')){
            case 'domain':
            case 'tplid':
                $val = $this->{$tag->getAttribute('name')};
                break;

            case 'resource_url':
                $val  = rtrim($this->domain, '/') . '/' . $this->tplid . '/';
                break;

            case 'page_total':
            case 'page_size':
            case 'page_num':
            case 'page_index':
            case 'page_url':
                $val = $this->pageInfo[$tag->getAttribute('name')];

                break;
            default:
                throw new HtmlPraseException('标签 "'.$tag->tagName.'"属性值"'.$tag->getAttribute('name').'"不存在 ('.$this->position($tag->posStart) . ')！');
        }

        $tag->assign($val);


//        dd($tag, $tag->getAttribute('name'));
        return $this;
    }

}

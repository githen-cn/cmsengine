<?php

namespace Githen\CmsEngine;


use Githen\CmsEngine\Exceptions\HtmlPraseException;
use Githen\CmsEngine\Lib\Tag;
use function GuzzleHttp\Psr7\str;

/**
 * HTML模板解析引擎
 *
 */
class HtmlPrase
{
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
     * 构建引擎所需要的配置信息
     *
     * @param array
     *
     * @return void
     */
    function __construct($options = [])
    {
        $this->setConfigs($options);
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
        $this->setHomedir($options['home']);

        $this->tagMaxLen = $options['tagmaxlen'] ?? 60;
        $this->toLow     = $options['tolow']     ?? TRUE;
    }

    /**
     * 设置标记名称
     *
     * @param string $name  标记名称
     * @param string $start 标记开始字符
     * @param string $end   标记结束字符
     *
     * @return void
     */
    public function setNameSpace($name, $strat = '{', $end = '}')
    {
        $this->nameSpace = $name;
        $this->tagStart  = $strat;
        $this->tagEnd    = $end;
    }

    /**
     * 设置模板目录
     *
     */
    public function setHomedir($dir)
    {
        $this->homeDir = $dir;
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

        $this->loadSource(file_get_contents($path));
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
        $this->parseTemplate();
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

        // 结束标识 {/
        $tagEnd = '/' . $this->tagEnd;

        $sourceLen = strlen($this->sourceHtml);

        // 长度过短，不执行解析
        if ($sourceLen <= $tagStartAllLen + 3){
            return;
        }

        // 标签处理截断标识
        $clipChar = ["/", "\r", "\t", "\n", " ", $this->tagEnd];


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
                throw new HtmlPraseException('标签 "'.$tmpTagName.'" 在位置'.$posCur . '错误！');
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
//            dump($tagObject, $innerText);

            if ($tagObject->tagName){
                $this->tagNum++;
                $tagObject->posStart = $posCur;
                $tagObject->posEnd = $i;
                $tagObject->innerText = $innerText;
                $this->tags[] = $tagObject;
            }
        }
    }

    /**
     * 渲染生成页面
     */
    public function fetch()
    {
        // 未解析到属性
        if (! $this->tagNum) return $this->sourceHtml;

        // 基础数据处理
        // field include ...


        // 对tag进行渲染
        $html = '';
        $nextPos = 0;
        foreach ($this->tags as $tag){
            $html .= substr($this->sourceHtml, $nextPos, $tag->posStart-$nextPos);
            $html .= $tag->tagVal;
            $nextPos = $tag->posEnd;
        }

        $sourceLen = strlen($this->sourceHtml);
        if ($sourceLen > $nextPos){
            $html .= substr($this->sourceHtml, $nextPos, $sourceLen - $nextPos);
        }

        return $html;
    }
}

<?php

namespace Githen\CmsEngine;

use Githen\CmsEngine\Exceptions\HtmlPraseException;

/**
 * 模板标签解析类
 */
class Tag
{
    /**
     * 是否使用更新内容
     * @var bool
     */
    public $isReplace = FALSE;

    /**
     * 标签名称
     * @var string
     */
    public $tagName = '';

    /**
     * 标签内容
     * @var string
     */
    public $tagVal = '';

    /**
     * 标签内容
     * @var string
     */
    public $innerText = '';

    /**
     * 模板中开始位置
     * @var int
     */
    public $posStart = 0;

    /**
     * 模板中结束位置
     * @var int
     */
    public $posEnd = 0;

    /**
     * 标签属性
     * @var object
     */
    public $attribute = [];

    /**
     * 标答及属性名称是否区分大小写
     *
     * @var bool
     */
    public $toLow = true;

    /**
     * 最大解析长度
     * @var int
     */
    private $sourceMax = 1024;

    /**
     * 设置需要解析的内容并进行解析
     *
     * $param string $str
     */
    public function setSource($str)
    {
        // 默认处理字符串格式
        $str = str_replace([" ", "\r", "\n", "\t"], " ", $str);
        $str = str_replace(['\]'], "]", trim($str));
        if (strlen($str) > $this->sourceMax){
            throw new HtmlPraseException('模板解析超过限长('.$this->sourceMax.')，当前长度:'.strlen($str),[$str]);
        }

        // 获取标签名称
        $tmpName = strstr($str, ' ', true);
        if ($tmpName === false){
            $tmpName = $str;
        }
        $tmpName = explode('.', $tmpName);
        $this->tagName = $this->toLow ? strtolower($tmpName[0]):$tmpName[0];

        if (! empty($tmpName[1])) $this->attribute['name'] = $tmpName[1];

        // 无属性标签
        if (strstr($str, ' ') === false){
            return;
        }

        $tmpStr = trim(strstr($str, ' '));
        $flag = -1;
        $field = $val = '';
        for ($i=0; $i<strlen($tmpStr); $i++){
            switch ($flag){
                case -1:
                    //  name='keywords'
                    if ($tmpStr[$i] == '='){
                        $field = $this->toLow ? strtolower($field) : $field;
                        $flag = 0;
                        break;
                    }
                    $field .= $tmpStr[$i];
                    break;
                case 0:
                    if ($tmpStr[$i] == ' ') break; // 未过滤的空格，跳过
                    if (in_array($tmpStr[$i],['"', "'"])){
                        $border = $tmpStr[$i];
                    }else{
                        $val .= $tmpStr[$i];
                        $border = ' ';
                    }
                    $flag = 1;

                    break;
                case 1:
                    if ($tmpStr[$i] == $border && isset($tmpStr[$i-1]) && $tmpStr[$i-1] != '\\'){
                        $field = trim($field);
                        $field = $this->toLow ? strtolower($field) : $field;

                        $this->attribute[$field] = trim($val);
                        $field = $val = '';
                        $flag = -1;
                        break;
                    }
                    $val .= $tmpStr[$i];
            }
        }
        if ($field){
            $this->attribute[$field] = trim($val);
        }
    }

    /**
     * 获取属性值
     */
    public function getAttribute($key)
    {
        return $this->attribute[$key] ?? null;
    }
    /**
     * 获取属性值
     */
    public function getAttributes()
    {
        return $this->attribute;
    }

    /**
     * 设置解析的值
     * @param string $str 要渲染的值
     * @param array $black 敏感词
     */
    public function assign($str, $black = [])
    {
        // 敏感词处理
        $str = str_replace($black, '***', $str);

        $this->isReplace = true;
        $this->tagVal = $str;
    }


}

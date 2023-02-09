<?php
namespace Githen\CmsEngine\Lib;

use Githen\CmsEngine\Exceptions\HtmlParseException;

/**
 * 扩展方法类
 */
class Functions
{
    /**
     * 扩展方法
     * @var
     */
    private $extFunctions;

    /**
     * 处理扩展方法
     * @throws HtmlParseException
     */
    public function __call($name, $params)
    {
        if ((! $this->extFunctions) || !method_exists($this->extFunctions, $name)) {
            throw new HtmlParseException("方法不存在：$name");
        }

        // 检测扩展方法中是否有此类
        return call_user_func([$this->extFunctions, $name], ...$params);
    }

    /**
     * 设置扩展类
     * @param object $object
     */
    public function setExtFunctions(object $object)
    {
        $this->extFunctions = $object;
    }

    /**
     * 限制字符串长度
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public function limit(string $value, int $limit = 15, string $end = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')).$end;
    }


}

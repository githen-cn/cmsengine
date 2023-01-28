<?php

namespace Githen\CmsEngine\Exceptions;

/**
 * 模板解析异常
 *
 */
class HtmlPraseException extends \Exception
{
    /**
     * 错误信息.
     *
     * @var array
     */
    public $errors;

    public function __construct($message = "", $errors = [])
    {
        $this->errors = $errors;

        parent::__construct($message);

    }

}

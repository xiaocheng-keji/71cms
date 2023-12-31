<?php

namespace app\common\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpResponseException;

/**
 * Created by PhpStorm.
 * User: millik
 * Date: 2019/1/24
 */
class BaseException extends Handle
{
    public function render(Exception $e)
    {
        $appRequest = request()->pathinfo();
        if ($appRequest === null) {
            $appName = '';
        } else {
            $appRequest = str_replace('//', '/', $appRequest);
            $appName = explode('/', $appRequest)[0] ?? '';
        }
        switch (strtolower($appName)) {
            case 'h5':
                //h5 history模式兼容
                return view(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'h5' . DIRECTORY_SEPARATOR . 'index.html');
                break;
        }
        // 如果是Ajax请求时发生异常
        if (request()->isAjax()) {
            /*$errorStr = $e->getMessage() . '<br/>' .
                $e->getFile() . ' Line:' . $e->getLine();
            return response($errorStr, $e->getCode());*/
        }

        if ($e instanceof HttpResponseException) {

        } else {
            // 其他错误交给系统处理
            return parent::render($e);
        }
    }
}
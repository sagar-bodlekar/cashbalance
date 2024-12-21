<?php
class CustomError
{
    public static function show404($message = 'पेज नहीं मिला')
    {
        http_response_code(404);
        $title = '404 Error';
        include dirname(__DIR__) . '/404.php';
        exit();
    }

    public static function handle($title = '404 Error', $message = 'पेज नहीं मिला')
    {
        http_response_code(404);
        include dirname(__DIR__) . '/404.php';
        exit();
    }
}

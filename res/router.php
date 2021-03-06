<?php

// Router for the PHP built-in server

date_default_timezone_set('UTC');
define('DIRECTORY_INDEX', 'index.html');
define('DEBUG', false);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

// watcher, called by livereload.js
if ($path == '/watcher') {
    http_response_code(200);
    if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/watch.flag')) {
        echo 'stop';
        exit();
    }
    if (file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/changes.flag')) {
        echo 'true';
        unlink($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/changes.flag');
    } else {
        echo 'false';
    }
    exit();
}
// ie: /blog/post-1/ -> /blog/post-1/index.html
if (empty($ext)) {
    $pathname = rtrim($path, '/').'/'.DIRECTORY_INDEX;
// ie: /css/style.css
} else {
    $pathname = $path;
}
if (file_exists($filename = $_SERVER['DOCUMENT_ROOT'].$pathname)) {
    $ext = pathinfo($pathname, PATHINFO_EXTENSION);
    $mimeshtml = ['xhtml+xml', 'html'];
    $mimestxt = ['json', 'xml', 'css', 'csv', 'javascript', 'plain', 'text'];

    // get file mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = finfo_file($finfo, $filename);
    $mime = explode('/', $mimetype)[1];
    finfo_close($finfo);

    // manipulate html and plain text file content for local serve
    if (in_array($mime, $mimeshtml) || in_array($mime, $mimestxt)) {
        $content = file_get_contents($filename);
        // html only
        if (in_array($mime, $mimeshtml)) {
            // inject live reload script
            if (file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/watch.flag')) {
                $script = file_get_contents(__DIR__.'/livereload.js');
                $content = str_replace('</body>', $script."\n".'</body>', $content);
            }
        }
        // replace `baseurl` by `http://localhost:8000/`
        $baseurl = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/baseurl'));
        if (false !== strstr($baseurl, 'http') || $baseurl != '/') {
            $content = str_replace($baseurl, 'http://localhost:8000/', $content);
        }
        // return result
        header('Content-Type: '.$mimetype);
        if ($ext == 'css') {
            header('Content-Type: text/css');
        }
        if ($ext == 'js') {
            header('Content-Type: application/javascript');
        }
        echo $content;

        return true;
    }

    return false;
}
http_response_code(404);
echo '404, page not found';

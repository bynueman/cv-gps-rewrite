<?php

use Illuminate\Http\Request;
use Tests\Support\LocalPreview;

if (PHP_SAPI !== 'cli-server' || ! in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}
if (! preg_match('~^(127[.]0[.]0[.]1|localhost|\\[::1\\])(:[0-9]+)?$~i', $_SERVER['HTTP_HOST'] ?? '')) {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_contains($uri, chr(0)) || preg_match('~(^|/)[.]|[.]php(?:/|$)~i', $uri)) {
    http_response_code(404);
    exit;
}
$uploads = str_starts_with($uri, '/uploads/');
$public = realpath($uploads ? $root.'/storage/app/private/gpsfood-local-preview/public' : $root.'/public');
$file = $public ? realpath($public.$uri) : false;
$extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)
    && in_array($extension, ['css', 'js', 'json', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'ico', 'woff2', 'pdf', 'txt'], true)) {
    if (! $uploads) {
        return false;
    }
    header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}
require $root.'/vendor/autoload.php';
$app = LocalPreview::boot();
$app->handleRequest(Request::capture());

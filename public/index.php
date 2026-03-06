<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Bootstrap Environment From Parent Process
|--------------------------------------------------------------------------
|
| PHP's built-in web server spawns worker processes that do not inherit
| the parent's environment variables. We read the parent process environ
| and copy any missing variables so that Dotenv and config() work correctly
| for DB, cache, session, and queue settings.
|
*/
if (function_exists('posix_getppid') && !getenv('DB_HOST')) {
    $ppidEnvFile = '/proc/' . posix_getppid() . '/environ';
    if (is_readable($ppidEnvFile)) {
        $parentEnv = @file_get_contents($ppidEnvFile);
        if ($parentEnv) {
            foreach (explode("\0", $parentEnv) as $entry) {
                if ($entry === '' || strpos($entry, '=') === false) continue;
                [$key, $val] = explode('=', $entry, 2);
                if ($key && !getenv($key)) {
                    putenv($entry);
                    $_ENV[$key]    = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

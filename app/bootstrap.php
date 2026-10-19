<?php
declare(strict_types=1);
define('ROOT',dirname(__DIR__));
if (is_file(ROOT.'/vendor/autoload.php')) require ROOT.'/vendor/autoload.php';
else spl_autoload_register(function(string $class): void { if (str_starts_with($class,'App\\')) { $file=ROOT.'/app/'.str_replace('\\','/',substr($class,4)).'.php'; if (is_file($file)) require $file; } });
require ROOT.'/app/Core/helpers.php';
\App\Core\Env::load(getenv('APP_ENV_FILE')?:ROOT.'/.env');
date_default_timezone_set('America/Lima');
ini_set('display_errors','0'); ini_set('log_errors','1');
foreach (['logs','sessions','backups'] as $dir) if (!is_dir(ROOT.'/storage/'.$dir)) mkdir(ROOT.'/storage/'.$dir,0700,true);
ini_set('error_log',ROOT.'/storage/logs/php.log');
if (PHP_SAPI!=='cli') {
    ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
    session_name('ISAM_SESSION'); session_save_path(ROOT.'/storage/sessions');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>\App\Core\Env::get('SESSION_SECURE')==='true','httponly'=>true,'samesite'=>'Lax']);
    session_start(); \App\Core\Security::headers();
}

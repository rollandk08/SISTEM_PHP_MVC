<?php
declare(strict_types=1);
$file=dirname(__DIR__).'/.env.testing';
if(!is_file($file))throw new RuntimeException('Cree .env.testing y ejecute APP_ENV_FILE=.env.testing php tools/console.php install.');
putenv('APP_ENV_FILE='.$file);
require dirname(__DIR__).'/app/bootstrap.php';
if(\App\Core\Env::get('APP_ENV')!=='testing' || !str_ends_with(\App\Core\Env::get('DB_DATABASE',''),'_test'))throw new RuntimeException('Pruebas bloqueadas: requieren APP_ENV=testing y base terminada en _test.');
$_SESSION=['user'=>['id'=>1,'role'=>'ADMIN']];

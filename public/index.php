<?php
declare(strict_types=1);
try {
    require dirname(__DIR__).'/app/bootstrap.php';
    $router=new \App\Core\Router;require ROOT.'/config/routes.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'],(string)($_GET['r']??'dashboard'));
} catch (\Throwable $e) {
    $status=$e instanceof \App\Core\HttpException?$e->status:500;http_response_code($status);
    $ref=bin2hex(random_bytes(6));error_log('['.$ref.'] '.get_class($e).' '.$e->getMessage().' '.$e->getFile().':'.$e->getLine());
    $message=$status===500?'No se pudo completar la operación. Verifique MySQL y la instalación. Referencia: '.$ref:$e->getMessage();
    $escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Northwind · Aviso</title><link rel="stylesheet" href="assets/vendor/bootstrap.min.css"><main class="container py-5"><h1>Operación no completada</h1><p>'.$escape($message).'</p><a class="btn btn-primary" href="index.php">Volver al sistema</a></main></html>';
}

<?php
declare(strict_types=1);
namespace App\Core;
use App\Middleware\AuthMiddleware;
final class Router {
    private array $routes=[];
    public function add(string $method,string $path,array $handler,array $roles=[]): void { $this->routes[$method][$path]=[$handler,$roles]; }
    public function dispatch(string $method,string $path): void {
        if (!isset($this->routes[$method][$path])) throw new HttpException(isset($this->routes['GET'][$path])?405:404,'Ruta no disponible.');
        [$handler,$roles]=$this->routes[$method][$path];
        if ($roles) AuthMiddleware::check($roles);
        if ($method==='POST') Security::verifyCsrf($_POST['_csrf']??null);
        // Serialize writes and snapshots/restores across application requests.
        $lock=$roles && $method==='POST'; $pdo=null;
        if ($lock) { $pdo=Database::connection(); if ((int)$pdo->query("SELECT GET_LOCK('isam_northwind_write',10)")->fetchColumn()!==1) throw new HttpException(503,'Sistema ocupado. Intente nuevamente.'); }
        try { (new $handler[0])->{$handler[1]}(); }
        finally { if ($pdo) $pdo->query("SELECT RELEASE_LOCK('isam_northwind_write')"); }
    }
}

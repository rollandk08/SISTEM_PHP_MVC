<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class Database {
    private static ?PDO $pdo=null;
    public static function connection(bool $serverOnly=false): PDO {
        if (!$serverOnly && self::$pdo) return self::$pdo;
        $db=Env::get('DB_DATABASE','isam_northwind');
        if (!preg_match('/^[a-zA-Z0-9_]+$/',$db)) throw new \RuntimeException('Nombre de base inválido.');
        if (Env::get('DB_PASSWORD','')==='' && Env::get('DB_ALLOW_EMPTY_PASSWORD')!=='true') throw new \RuntimeException('Configure una contraseña de base de datos.');
        $dsn='mysql:host='.Env::get('DB_HOST','127.0.0.1').';port='.Env::get('DB_PORT','3306').($serverOnly?'':';dbname='.$db).';charset=utf8mb4';
        $pdo=new PDO($dsn,Env::get('DB_USERNAME','root'),Env::get('DB_PASSWORD',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec("SET time_zone = '-05:00'");
        if (!$serverOnly) self::$pdo=$pdo;
        return $pdo;
    }
    public static function transaction(callable $fn) {
        $pdo=self::connection(); $pdo->beginTransaction();
        try { $result=$fn($pdo); $pdo->commit(); return $result; }
        catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}

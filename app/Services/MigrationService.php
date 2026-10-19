<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Env};
final class MigrationService {
    public function run(): void {
        $server=Database::connection(true);$db=Env::get('DB_DATABASE');
        $q=$server->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?');$q->execute([$db]);
        if((int)$q->fetchColumn()>0){$q=$server->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name='schema_migrations'");$q->execute([$db]);if(!$q->fetchColumn())throw new \RuntimeException('La base contiene tablas ajenas. Use una base nueva; no se sobrescribirá.');}
        $server->exec('CREATE DATABASE IF NOT EXISTS `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo=Database::connection();$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(100) PRIMARY KEY,checksum CHAR(64) NOT NULL,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        foreach(glob(ROOT.'/migrations/*.sql') as $file){$version=basename($file);$checksum=hash_file('sha256',$file);$q=$pdo->prepare('SELECT checksum FROM schema_migrations WHERE version=?');$q->execute([$version]);$existing=$q->fetchColumn();if($existing){if(!hash_equals($existing,$checksum))throw new \RuntimeException('Migración aplicada modificada: '.$version);continue;}
            echo 'Aplicando '.$version.PHP_EOL;$pdo->exec(file_get_contents($file));$pdo->prepare('INSERT INTO schema_migrations(version,checksum) VALUES(?,?)')->execute([$version,$checksum]);
        }
    }
    public function seed(): void {
        $pdo=Database::connection();if((int)$pdo->query('SELECT COUNT(*) FROM app_users')->fetchColumn()>0){echo "Usuarios existentes conservados.\n";return;}
        Database::transaction(function($pdo){$q=$pdo->prepare('INSERT INTO app_users(name,email,role,password_hash) VALUES(?,?,?,?)');foreach([['Administrador ISAM','admin@isam.local','ADMIN','Admin#ISAM2026!'],['Operador ISAM','operador@isam.local','OPERADOR','Opera#ISAM2026!'],['Consulta ISAM','consulta@isam.local','CONSULTA','Consulta#ISAM2026!']] as [$name,$email,$role,$pass])$q->execute([$name,$email,$role,password_hash($pass,PASSWORD_BCRYPT,['cost'=>12])]);});
        echo "Cuentas locales de demostración creadas. Consulte README.md y cambie sus contraseñas.\n";
    }
}

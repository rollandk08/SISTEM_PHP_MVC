<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,Env,HttpException};
final class BackupService {
    private function directory(): string { $db=Env::get('DB_DATABASE','');if(!preg_match('/^[a-zA-Z0-9_]+$/',$db))throw new \RuntimeException('Base inválida.');$dir=ROOT.'/storage/backups/'.$db;if(!is_dir($dir))mkdir($dir,0700,true);return $dir; }
    private function key(): string {$key=Env::get('APP_KEY','');if(strlen($key)<32)throw new \RuntimeException('APP_KEY debe contener al menos 32 caracteres.');return $key;}
    public function tables(): array {return Database::connection()->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);}
    public function schema(): array {$schema=[];$pdo=Database::connection();foreach($this->tables() as $table){$schema[$table]=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(\PDO::FETCH_NUM)[1];$schema[$table]=preg_replace('/ AUTO_INCREMENT=\d+/','',$schema[$table]);}ksort($schema);return $schema;}
    public function create(): string {
        $pdo=Database::connection();$locked=(int)$pdo->query("SELECT GET_LOCK('isam_northwind_write',10)")->fetchColumn();if($locked!==1)throw new HttpException(503,'No se pudo bloquear la base para el respaldo.');
        try {
            $schema=$this->schema();$tables=Database::transaction(function($pdo)use($schema){$tables=[];foreach($schema as $table=>$ddl){$rows=[];foreach($pdo->query('SELECT * FROM `'.$table.'`') as $row){$r=[];foreach($row as $k=>$v)$r[$k]=$v===null?null:base64_encode((string)$v);$rows[]=$r;}$tables[$table]=['columns'=>array_column($pdo->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),'Field'),'rows'=>$rows];}return $tables;});
            $payload=json_encode(['format'=>1,'database'=>Env::get('DB_DATABASE'),'created_at'=>date(DATE_ATOM),'schema_hash'=>hash('sha256',json_encode($schema,JSON_THROW_ON_ERROR)),'tables'=>$tables],JSON_THROW_ON_ERROR);
            $file='northwind-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';$json=json_encode(['hmac'=>hash_hmac('sha256',$payload,$this->key()),'payload'=>$payload],JSON_THROW_ON_ERROR);
            if(file_put_contents($this->directory().'/'.$file,$json,LOCK_EX)===false)throw new \RuntimeException('No se pudo guardar el respaldo.');
            AuditService::log('BACKUP','database',$file);return $file;
        }finally{$pdo->query("SELECT RELEASE_LOCK('isam_northwind_write')");}
    }
    public function path(string $file): string {if(!preg_match('/^northwind-\d{8}-\d{6}-[a-f0-9]{8}\.json$/',$file) || !is_file($this->directory().'/'.$file))throw new HttpException(404,'Respaldo no encontrado.');return $this->directory().'/'.$file;}
    public function inspect(string $file): array {
        $data=json_decode(file_get_contents($this->path($file)),true,512,JSON_THROW_ON_ERROR);
        if(!isset($data['hmac'],$data['payload']) || !hash_equals(hash_hmac('sha256',$data['payload'],$this->key()),$data['hmac']))throw new HttpException(422,'La firma del respaldo no es válida.');
        $payload=json_decode($data['payload'],true,512,JSON_THROW_ON_ERROR);
        if(($payload['format']??0)!==1 || !hash_equals(hash('sha256',json_encode($this->schema(),JSON_THROW_ON_ERROR)),$payload['schema_hash']??''))throw new HttpException(422,'El esquema del respaldo es incompatible.');
        $expected=$this->tables();$actual=array_keys($payload['tables']??[]);sort($expected);sort($actual);if($actual!==$expected)throw new HttpException(422,'El respaldo no contiene las tablas esperadas.');
        foreach($payload['tables'] as $table=>$t){$columns=array_column(Database::connection()->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),'Field');if($columns!==$t['columns'])throw new HttpException(422,'Columnas incompatibles.');foreach($t['rows'] as $row){if(array_keys($row)!==$columns)throw new HttpException(422,'Fila incompatible.');foreach($row as $value)if($value!==null && base64_decode($value,true)===false)throw new HttpException(422,'Contenido inválido.');}}
        return $payload;
    }
    public function restore(string $file): void {
        $payload=$this->inspect($file);$this->create(); // Recovery point is always created before changing data.
        $pdo=Database::connection();if((int)$pdo->query("SELECT GET_LOCK('isam_northwind_write',10)")->fetchColumn()!==1)throw new HttpException(503,'Base ocupada.');
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            Database::transaction(function($pdo)use($payload,$file){
                foreach(array_reverse(array_keys($payload['tables'])) as $table)$pdo->exec('DELETE FROM `'.$table.'`');
                foreach($payload['tables'] as $table=>$t){
                    $cols=$t['columns'];$integer=[];foreach($pdo->query('SHOW COLUMNS FROM `'.$table.'`') as $col)$integer[$col['Field']]=(bool)preg_match('/^(tinyint|smallint|mediumint|int|bigint|bit)\b/i',$col['Type']);
                    $q=$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES('.implode(',',array_fill(0,count($cols),'?')).')');
                    foreach($t['rows'] as $row){foreach($cols as $i=>$col){$value=$row[$col]===null?null:base64_decode($row[$col],true);$type=$value===null?\PDO::PARAM_NULL:($integer[$col]?\PDO::PARAM_INT:\PDO::PARAM_STR);$q->bindValue($i+1,$integer[$col] && $value!==null?(int)$value:$value,$type);}$q->execute();}
                }
                $q=$pdo->query("SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach($q->fetchAll() as $fk){$sql='SELECT COUNT(*) FROM `'.$fk['TABLE_NAME'].'` c LEFT JOIN `'.$fk['REFERENCED_TABLE_NAME'].'` p ON c.`'.$fk['COLUMN_NAME'].'`=p.`'.$fk['REFERENCED_COLUMN_NAME'].'` WHERE c.`'.$fk['COLUMN_NAME'].'` IS NOT NULL AND p.`'.$fk['REFERENCED_COLUMN_NAME'].'` IS NULL';if($pdo->query($sql)->fetchColumn())throw new HttpException(422,'El respaldo presenta referencias huérfanas.');}
                $pdo->exec('UPDATE app_users SET session_version=session_version+1');AuditService::log('RESTORE','database',$file);
            });
        }finally{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');$pdo->query("SELECT RELEASE_LOCK('isam_northwind_write')");}
    }
    public function listing(): array {$list=[];foreach(glob($this->directory().'/northwind-*.json') as $file)$list[]=['name'=>basename($file),'size'=>filesize($file),'date'=>date('d/m/Y H:i',filemtime($file))];return array_reverse($list);}
}

<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,HttpException};
use App\Models\Catalog;
final class ImportService {
    private function model(string $module): Catalog { if(!in_array($module,['clientes','proveedores'],true))throw new HttpException(422,'Importación no permitida.');return new Catalog($module); }
    public function validateRow(Catalog $m,array $raw,array &$seen): array {
        [$data,$errors]=ValidationService::catalog($m->meta,$raw);
        $name=mb_strtolower(trim((string)($data['CompanyName']??'')));$key=$m->module==='clientes'?(string)($data['CustomerID']??''):$name;
        if(isset($seen['id:'.$key]) || isset($seen['name:'.$name])) $errors['_duplicate']='Duplicado dentro del CSV.';
        $seen['id:'.$key]=true;$seen['name:'.$name]=true;
        $sql='SELECT COUNT(*) FROM `'.$m->meta['table'].'` WHERE LOWER(TRIM(CompanyName))=?';$params=[$name];
        if($m->module==='clientes'){$sql.=' OR CustomerID=?';$params[]=$key;}
        $q=Database::connection()->prepare($sql);$q->execute($params);if($q->fetchColumn())$errors['_database']='La empresa o código ya existe en la base de datos.';
        return [$data,$errors];
    }
    public function preview(string $module,string $path,string $name): int {
        $m=$this->model($module);if(filesize($path)>2*1024*1024)throw new HttpException(422,'El archivo supera 2 MB.');
        $fh=fopen($path,'rb');$headers=fgetcsv($fh);if(!$headers){fclose($fh);throw new HttpException(422,'CSV vacío.');}
        $headers[0]=preg_replace('/^\xEF\xBB\xBF/','',$headers[0]);$headers=array_map('trim',$headers);
        $allowed=array_keys($m->meta['fields']);$required=$module==='clientes'?['CustomerID','CompanyName']:['CompanyName'];
        if(count(array_unique($headers))!==count($headers) || array_diff($required,$headers) || array_diff($headers,$allowed)){fclose($fh);throw new HttpException(422,'Encabezados inválidos. Use la plantilla CSV.');}
        $rows=[];$seen=[];$line=1;
        try{while(($values=fgetcsv($fh))!==false){$line++;if($values===[null])continue;if(count($rows)>=1000)throw new HttpException(422,'Máximo 1000 filas por archivo.');
            if(count($values)!==count($headers)){$rows[]=[$line,[],['CSV'=>'Número de columnas incorrecto.']];continue;}
            foreach($values as $v)if(!mb_check_encoding((string)$v,'UTF-8') || str_contains((string)$v,"\0"))throw new HttpException(422,'El archivo debe usar UTF-8 sin caracteres nulos.');
            $raw=array_combine($headers,$values);[$data,$errors]=$this->validateRow($m,$raw,$seen);$rows[]=[$line,$data,$errors];
        }}finally{fclose($fh);}if(!$rows)throw new HttpException(422,'No hay filas para importar.');
        return Database::transaction(function($pdo)use($module,$name,$rows){
            $hasErrors=(bool)array_filter($rows,fn($r)=>(bool)$r[2]);$q=$pdo->prepare('INSERT INTO app_imports(user_id,module,filename,status,row_count,errors) VALUES(?,?,?,?,?,?)');$q->execute([$_SESSION['user']['id'],$module,mb_substr(basename($name),0,180),$hasErrors?'INVALIDO':'PREVIA',count($rows),$hasErrors?'Corrija el archivo y vuelva a cargarlo.':null]);$id=(int)$pdo->lastInsertId();
            $q=$pdo->prepare('INSERT INTO app_import_rows(import_id,line_number,payload,errors) VALUES(?,?,?,?)');foreach($rows as [$line,$data,$errors])$q->execute([$id,$line,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($errors,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            AuditService::log('IMPORT_PREVIEW',$module,$id,['rows'=>count($rows),'valid'=>!$hasErrors]);return $id;
        });
    }
    public function find(int $id): array {
        $q=Database::connection()->prepare('SELECT * FROM app_imports WHERE id=? AND user_id=?');$q->execute([$id,$_SESSION['user']['id']]);$batch=$q->fetch();if(!$batch)throw new HttpException(404,'Importación no encontrada.');
        $q=Database::connection()->prepare('SELECT * FROM app_import_rows WHERE import_id=? ORDER BY line_number');$q->execute([$id]);$batch['rows']=$q->fetchAll();return $batch;
    }
    public function commit(int $id): void {
        Database::transaction(function($pdo)use($id){
            $q=$pdo->prepare('SELECT * FROM app_imports WHERE id=? AND user_id=? FOR UPDATE');$q->execute([$id,$_SESSION['user']['id']]);$batch=$q->fetch();
            if(!$batch || $batch['status']!=='PREVIA' || strtotime($batch['created_at'])<time()-3600)throw new HttpException(422,'Vista previa inválida, vencida o ya procesada.');
            $m=$this->model($batch['module']);$q=$pdo->prepare('SELECT payload FROM app_import_rows WHERE import_id=? ORDER BY line_number');$q->execute([$id]);$seen=[];
            foreach($q->fetchAll() as $r){[$data,$errors]=$this->validateRow($m,json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR),$seen);if($errors)throw new HttpException(422,'La base cambió o hay duplicados. Genere una nueva vista previa.');$m->insert($data);}
            $pdo->prepare("UPDATE app_imports SET status='COMPLETADO' WHERE id=?")->execute([$id]);AuditService::log('IMPORT_COMMIT',$m->meta['table'],$id,['rows'=>$batch['row_count']]);
        });
    }
}

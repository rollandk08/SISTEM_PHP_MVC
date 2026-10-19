<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\{Database,HttpException};
final class Catalog {
    public array $meta; public string $module;
    public function __construct(string $module) { $modules=require ROOT.'/config/modules.php'; if (!isset($modules[$module])) throw new HttpException(404,'Módulo no disponible.'); $this->module=$module; $this->meta=$modules[$module]; }
    public function find($id): array { $q=Database::connection()->prepare('SELECT *'.($this->module==='productos'?',CAST(Discontinued AS UNSIGNED) AS Discontinued':'').' FROM `'.$this->meta['table'].'` WHERE `'.$this->meta['pk'].'`=?'); $q->execute([$id]); $row=$q->fetch(); if (!$row) throw new HttpException(404,'Registro no encontrado.'); return $row; }
    public function conditions(array $input): array {
        $where=[]; $params=[];
        if (trim((string)($input['q']??''))!=='') { $parts=[]; foreach ($this->meta['search'] as $column) { $parts[]='`'.$column.'` LIKE ?'; $params[]='%'.mb_substr(trim($input['q']),0,100).'%'; } $where[]='('.implode(' OR ',$parts).')'; }
        if (!empty($this->meta['filter']) && ($input['filter']??'')!=='') { $where[]='`'.$this->meta['filter'].'`=?'; $params[]=mb_substr((string)$input['filter'],0,100); }
        return [$where?' WHERE '.implode(' AND ',$where):'',$params];
    }
    public function listing(array $input,bool $all=false): array {
        [$where,$params]=$this->conditions($input); $pdo=Database::connection();
        $q=$pdo->prepare('SELECT COUNT(*) FROM `'.$this->meta['table'].'`'.$where); $q->execute($params); $total=(int)$q->fetchColumn();
        $page=max(1,min(max(1,(int)ceil($total/15)),(int)($input['page']??1)));
        $q=$pdo->prepare('SELECT `'.implode('`,`',$this->meta['list']).'` FROM `'.$this->meta['table'].'`'.$where.' ORDER BY `'.$this->meta['pk'].'`'.($all?'':' LIMIT 15 OFFSET '.(($page-1)*15))); $q->execute($params);
        $rows=$q->fetchAll();if($this->module==='productos')foreach($rows as &$row)$row['Discontinued']=is_string($row['Discontinued']) && in_array($row['Discontinued'],["\0","\1"],true)?ord($row['Discontinued']):(int)$row['Discontinued'];unset($row);
        return ['rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/15)];
    }
    public function options(): array {
        $out=[]; $pdo=Database::connection();
        foreach ($this->meta['fields'] as $column=>$field) if ($field['type']==='select') $out[$column]=$pdo->query('SELECT `'.$field['key'].'` AS id,`'.$field['name'].'` AS label FROM `'.$field['table'].'` ORDER BY 2')->fetchAll();
        if (($this->meta['filter']??'')==='Country') $out['Country']=$pdo->query('SELECT DISTINCT Country AS id,Country AS label FROM `'.$this->meta['table'].'` WHERE Country IS NOT NULL ORDER BY Country')->fetchAll();
        return $out;
    }
    public function insert(array $data) { $columns=array_keys($data); $q=Database::connection()->prepare('INSERT INTO `'.$this->meta['table'].'` (`'.implode('`,`',$columns).'`) VALUES('.implode(',',array_map(fn($c)=>$c==='Discontinued'?'CAST(? AS UNSIGNED)':'?',$columns)).')'); $q->execute(array_values($data)); return $this->meta['auto']?Database::connection()->lastInsertId():$data[$this->meta['pk']]; }
    public function update($id,array $data): void { unset($data[$this->meta['pk']]); $sets=array_map(fn($k)=>'`'.$k.'`='.($k==='Discontinued'?'CAST(? AS UNSIGNED)':'?'),array_keys($data)); $q=Database::connection()->prepare('UPDATE `'.$this->meta['table'].'` SET '.implode(',',$sets).' WHERE `'.$this->meta['pk'].'`=?'); $q->execute([...array_values($data),$id]); }
    public function delete($id): void { $q=Database::connection()->prepare('DELETE FROM `'.$this->meta['table'].'` WHERE `'.$this->meta['pk'].'`=?'); $q->execute([$id]); }
}

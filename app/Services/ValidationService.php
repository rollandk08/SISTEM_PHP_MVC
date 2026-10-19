<?php
declare(strict_types=1);
namespace App\Services;
final class ValidationService {
    public static function catalog(array $meta,array $input,bool $editing=false): array {
        $data=[]; $errors=[];
        foreach ($meta['fields'] as $column=>$f) {
            if ($editing && $column===$meta['pk']) continue;
            $v=trim((string)($input[$column]??''));
            if (($f['required']??false) && $v==='') $errors[$column]=$f['label'].': campo obligatorio.';
            if (isset($f['max']) && !in_array($f['type'],['number','select']) && mb_strlen($v)>$f['max']) $errors[$column]=$f['label'].': supera '.$f['max'].' caracteres.';
            if ($f['type']==='number') {
                if ($v==='') $v='0';
                $pattern=($f['decimal']??false)?'/^\d+(\.\d{1,4})?$/':'/^\d+$/';
                if (!preg_match($pattern,$v) || (float)$v<($f['min']??0) || (float)$v>$f['max']) $errors[$column]=$f['label'].': número fuera de rango.';
            }
            if ($f['type']==='select' && $v!=='' && !ctype_digit($v)) $errors[$column]='Seleccione una opción válida.';
            if ($f['type']==='checkbox') $v=isset($input[$column]) && $input[$column]==='1' ? '1':'0';
            $data[$column]=$v===''?null:$v;
        }
        if ($meta['pk']==='CustomerID' && !$editing && !preg_match('/^[A-Z0-9]{5}$/',(string)($data['CustomerID']??''))) $errors['CustomerID']='El código debe tener exactamente 5 letras mayúsculas o números.';
        return [$data,$errors];
    }
}

<?php
declare(strict_types=1);
namespace App\Core;
abstract class Controller {
    protected function view(string $view,array $variables=[]): void {
        extract($variables,EXTR_SKIP); ob_start();
        try { require ROOT.'/app/Views/'.$view.'.php'; $content=ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
        require ROOT.'/app/Views/layout.php';
    }
    protected function redirect(string $route,array $params=[]): void { header('Location: '.url($route,$params),true,303); exit; }
    protected function flash(string $message,string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
    protected function csv(string $name,array $headers,iterable $rows): void {
        header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="'.$name.'.csv"');
        $out=fopen('php://output','wb'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,$headers);
        foreach ($rows as $row) fputcsv($out,array_map([Security::class,'csvCell'],array_values($row))); fclose($out);
    }
}

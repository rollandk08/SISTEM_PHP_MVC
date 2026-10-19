<?php
namespace App\Controllers;
use App\Core\{Controller,Database,HttpException};
use App\Services\BackupService;
final class BackupController extends Controller {
    public function index(): void { $b=new BackupService;$this->view('admin/backups',['title'=>'Respaldos y recuperación','rows'=>$b->listing(),'preview'=>isset($_GET['file'])?$b->inspect($_GET['file']):null,'file'=>$_GET['file']??null]); }
    public function create(): void { $file=(new BackupService)->create();$this->flash('Respaldo íntegro creado: '.$file);$this->redirect('backups'); }
    public function download(): void { $file=(string)($_GET['file']??'');$path=(new BackupService)->path($file);header('Content-Type: application/json');header('Content-Disposition: attachment; filename="'.basename($file).'"');header('Content-Length: '.filesize($path));readfile($path); }
    public function restore(): void {
        $q=Database::connection()->prepare('SELECT password_hash FROM app_users WHERE id=?');$q->execute([$_SESSION['user']['id']]);
        if(($_POST['confirmation']??'')!=='RESTAURAR' || !password_verify($_POST['password']??'',$q->fetchColumn()))throw new HttpException(422,'Escriba RESTAURAR y confirme su contraseña para reemplazar los datos.');
        (new BackupService)->restore((string)($_POST['file']??''));$_SESSION=[];session_regenerate_id(true);$this->redirect('login');
    }
}

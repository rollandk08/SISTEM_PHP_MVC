<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Database,HttpException};
use App\Services\AuditService;
final class AdminController extends Controller {
    public function audit(): void { $q=Database::connection()->query('SELECT a.*,u.name FROM app_audit a LEFT JOIN app_users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 200');$this->view('admin/audit',['title'=>'Auditoría del sistema','rows'=>$q->fetchAll()]); }
    public function incidents(): void { $this->view('admin/incidents',['title'=>'Registro de incidencias','rows'=>Database::connection()->query('SELECT i.*,u.name FROM app_incidents i JOIN app_users u ON u.id=i.user_id ORDER BY i.id DESC LIMIT 100')->fetchAll()]); }
    public function incidentSave(): void {
        if(isset($_POST['id'])) { if(!is_admin())throw new HttpException(403,'Solo ADMIN puede cambiar el estado.');if(!in_array($_POST['status']??'',['ABIERTA','EN_PROCESO','RESUELTA'],true))throw new HttpException(422,'Estado inválido.');Database::transaction(function($pdo){$pdo->prepare('UPDATE app_incidents SET status=? WHERE id=?')->execute([$_POST['status'],(int)$_POST['id']]);AuditService::log('INCIDENT_STATUS','incidents',(int)$_POST['id']);}); }
        else { $title=trim($_POST['title']??'');$description=trim($_POST['description']??'');$severity=$_POST['severity']??'';if($title==='' || mb_strlen($title)>150 || $description==='' || mb_strlen($description)>5000 || !in_array($severity,['BAJA','MEDIA','ALTA','CRITICA'],true))throw new HttpException(422,'Complete título, descripción (hasta 5000 caracteres) y severidad.');Database::transaction(function($pdo)use($title,$description,$severity){$pdo->prepare('INSERT INTO app_incidents(user_id,title,description,severity) VALUES(?,?,?,?)')->execute([$_SESSION['user']['id'],$title,$description,$severity]);AuditService::log('INCIDENT_CREATE','incidents',$pdo->lastInsertId());}); }
        $this->flash('Incidencia actualizada.');$this->redirect('incidents');
    }
    public function users(): void { $this->view('admin/users',['title'=>'Usuarios y permisos','rows'=>Database::connection()->query('SELECT id,name,email,role,active,created_at FROM app_users ORDER BY id')->fetchAll()]); }
    public function userSave(): void {
        $name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$role=$_POST['role']??'';$password=$_POST['password']??'';
        if($name==='' || mb_strlen($name)>100 || strlen($email)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !in_array($role,['ADMIN','OPERADOR','CONSULTA'],true) || strlen($password)<12 || strlen($password)>72)throw new HttpException(422,'Revise nombre, correo, rol y contraseña de 12 a 72 caracteres.');
        try{Database::transaction(function($pdo)use($name,$email,$role,$password){$pdo->prepare('INSERT INTO app_users(name,email,role,password_hash) VALUES(?,?,?,?)')->execute([$name,$email,$role,password_hash($password,PASSWORD_BCRYPT)]);AuditService::log('USER_CREATE','users',$pdo->lastInsertId(),['role'=>$role]);});}catch(\PDOException $e){if($e->getCode()!=='23000')throw $e;throw new HttpException(422,'El correo ya está registrado.');}
        $this->flash('Usuario creado.');$this->redirect('users');
    }
    public function userToggle(): void { $id=(int)($_POST['id']??0);if($id===(int)$_SESSION['user']['id'])throw new HttpException(422,'No puede desactivar su propia cuenta.');Database::transaction(function($pdo)use($id){$pdo->prepare('UPDATE app_users SET active=1-active,session_version=session_version+1 WHERE id=?')->execute([$id]);AuditService::log('USER_TOGGLE','users',$id);});$this->redirect('users'); }
}

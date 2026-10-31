<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Database,HttpException};
use App\Models\User;
use App\Services\AuditService;

final class AuthController extends Controller {
    public function form(): void { $this->view('auth/login',['title'=>'Iniciar sesión','error'=>null]); }
    public function login(): void {
        $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
        $pdo=Database::connection(); $hash=hash('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.$email);
        $q=$pdo->prepare('SELECT COUNT(*) FROM app_login_attempts WHERE identity_hash=? AND attempted_at > DATE_SUB(NOW(),INTERVAL 15 MINUTE)'); $q->execute([$hash]);
        if ((int)$q->fetchColumn()>=5) throw new HttpException(429,'Demasiados intentos. Espere 15 minutos.');
        $u=(new User)->byEmail($email);
        $valid=password_verify($password,$u['password_hash']??'$2y$10$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW');
        if (!$u || !$valid) { $pdo->prepare('INSERT INTO app_login_attempts(identity_hash) VALUES(?)')->execute([$hash]); $this->view('auth/login',['title'=>'Iniciar sesión','error'=>'Correo o contraseña incorrectos.']); return; }
        $pdo->prepare('DELETE FROM app_login_attempts WHERE identity_hash=?')->execute([$hash]);
        session_regenerate_id(true); unset($u['password_hash']); $_SESSION['user']=$u; $_SESSION['last_activity']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32));
        AuditService::log('LOGIN','users',$u['id']); $this->redirect('dashboard');
    }
    public function logout(): void { AuditService::log('LOGOUT','users',$_SESSION['user']['id']); $_SESSION=[]; session_destroy(); setcookie(session_name(),'',time()-3600,'/'); $this->redirect('login'); }
    public function password(): void {
        $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $q=Database::connection()->prepare('SELECT password_hash FROM app_users WHERE id=?'); $q->execute([$_SESSION['user']['id']]);
            if (!password_verify($_POST['current']??'', $q->fetchColumn())) $error='Contraseña actual incorrecta.';
            elseif (strlen($_POST['password']??'')<12 || strlen($_POST['password']??'')>72 || ($_POST['password']??'')!==($_POST['confirmation']??'')) $error='Use entre 12 y 72 caracteres y confirme la misma contraseña.';
            else {
                Database::transaction(function($pdo) { $pdo->prepare('UPDATE app_users SET password_hash=?,session_version=session_version+1 WHERE id=?')->execute([password_hash($_POST['password'],PASSWORD_BCRYPT),$_SESSION['user']['id']]); AuditService::log('PASSWORD_CHANGE','users',$_SESSION['user']['id']); });
                $_SESSION=[]; session_regenerate_id(true); $this->redirect('login');
            }
        }
        $this->view('auth/password',['title'=>'Cambiar contraseña','error'=>$error]);
    }
}
